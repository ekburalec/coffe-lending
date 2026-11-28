"""Isolated acceptance for checkout, manager workflow, SMTP and cleanup. --serve keeps QA site open."""
from pathlib import Path
import http.cookiejar
import json
import os
import re
import secrets
import socketserver
import sqlite3
import subprocess
import sys
import tempfile
import threading
import time
import urllib.error
import urllib.parse
import urllib.request

ROOT = Path(os.environ.get('COFFEE_TEST_ROOT', Path(__file__).resolve().parents[1])).resolve()
PORT = int(os.environ.get('COFFEE_ORDER_TEST_PORT', '8098' if '--serve' in sys.argv else '8099'))


class SMTP(socketserver.StreamRequestHandler):
    messages = []

    def handle(self):
        self.wfile.write(b'220 test\r\n')
        while line := self.rfile.readline():
            command = line.upper()
            if command.startswith(b'DATA'):
                self.wfile.write(b'354 go\r\n')
                message = b''
                while (line := self.rfile.readline()) != b'.\r\n':
                    if not line:
                        return
                    message += line
                self.messages.append(message)
                self.wfile.write(b'250 ok\r\n')
            elif command.startswith(b'QUIT'):
                self.wfile.write(b'221 bye\r\n')
                return
            else:
                self.wfile.write(b'250 ok\r\n')


def run():
    with tempfile.TemporaryDirectory(prefix='coffee-orders-') as directory:
        env = dict(os.environ, APP_DATA_DIR=directory, APP_UPLOAD_DIR=str(Path(directory) / 'media'), APP_PASSWORD_HIBP='0')

        def php(*args, input=None):
            result = subprocess.run(['php', '-d', 'extension=zip', '-d', 'extension=gd', *args], cwd=ROOT, env=env, input=input, text=True, capture_output=True)
            assert result.returncode == 0, result.stderr + result.stdout
            return result.stdout

        php('bin/migrate.php')
        php('bin/migrate.php')
        php('bin/create-admin.php', 'ordertest', input='QA-orders-L9!temporary-G7!only')
        db = sqlite3.connect(str(Path(directory) / 'site.sqlite'))
        def settings(key, value):
            db.execute('INSERT OR REPLACE INTO settings(name,payload) VALUES(?,?)', (key, json.dumps(value, ensure_ascii=False)))
            db.commit()
        settings('orders', dict(pause_message='Приём приостановлен', enabled=1, minimum=0, cutoff=0, horizon=30, company='Тестовое ИП', inn='000000000000', privacy_email='privacy@example.test'))
        delivery = json.loads(db.execute("SELECT payload FROM settings WHERE name='delivery'").fetchone()[0])
        delivery.update(fee=200, zone='Екатеринбург')
        settings('delivery', delivery)
        for row in db.execute("SELECT id,payload FROM records WHERE kind='hours'").fetchall():
            data = json.loads(row[1]); data.update(opens='00:00', closes='23:59', closed=0)
            db.execute("UPDATE records SET payload=? WHERE kind='hours' AND id=?", (json.dumps(data), row[0]))
        db.commit()
        log = open(Path(directory) / 'server.log', 'w')
        server = subprocess.Popen(['php', '-d', 'extension=gd', '-d', 'extension=zip', '-S', f'localhost:{PORT}', 'router.php'], cwd=ROOT, env=env, stdout=subprocess.DEVNULL, stderr=log)
        client = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
        manager = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
        def request(path, data=None, opener=client, files=None):
            body = urllib.parse.urlencode(data).encode() if data is not None else None
            headers = {}
            if files:
                boundary = 'orders-' + secrets.token_hex(12)
                chunks = []
                for key, value in data.items():
                    chunks.append(f'--{boundary}\r\nContent-Disposition: form-data; name="{key}"\r\n\r\n{value}\r\n'.encode())
                for key, content in files.items():
                    chunks.append(f'--{boundary}\r\nContent-Disposition: form-data; name="{key}"; filename="backup.sqlite"\r\nContent-Type: application/octet-stream\r\n\r\n'.encode() + content + b'\r\n')
                chunks.append(f'--{boundary}--\r\n'.encode())
                body = b''.join(chunks); headers['Content-Type'] = 'multipart/form-data; boundary=' + boundary
            try:
                response = opener.open(urllib.request.Request(f'http://localhost:{PORT}{path}', body, headers), timeout=10)
            except urllib.error.HTTPError as error:
                response = error
            return response.status, response.read().decode(), response.headers
        def field(html, name):
            match = re.search(r'name="' + re.escape(name) + r'" value="([^"]*)"', html)
            assert match, name
            return match[1]
        try:
            for _ in range(50):
                try:
                    request('/checkout'); break
                except OSError:
                    time.sleep(.1)
            status, html, headers = request('/checkout')
            assert status == 200 and 'no-store' in headers['Cache-Control']
            csrf = field(html, 'csrf')
            assert request('/checkout', dict(csrf='wrong', action='cart'))[0] == 403
            data = dict(csrf=csrf, action='cart', receiving='delivery', **{'quantities[americano]': '2'})
            status, html, _ = request('/checkout', data)
            assert status == 200 and 'checkout-details' in html
            post = dict(csrf=csrf, action='place', request_key=field(html, 'request_key'), fingerprint=field(html, 'fingerprint'), first_name='Тест', phone='+7 900 123-45-67', street='Ленина', house='1', email='client@example.test', consent='1')
            bad = dict(post, street='')
            assert request('/checkout', bad)[0] == 422
            status, html, _ = request('/checkout', post)
            assert status == 200 and 'VP-000001' in html, html
            assert request('/checkout', post)[0] == 200
            assert db.execute('SELECT COUNT(*) FROM orders').fetchone()[0] == 1
            customer = json.loads(db.execute('SELECT customer FROM orders').fetchone()[0])
            assert customer['street'] == 'Ленина' and customer['house'] == '1'
            assert db.execute('SELECT total FROM orders').fetchone()[0] == 580
            db.execute('UPDATE order_attempts SET attempts=20'); db.commit()
            _, limited_html, _ = request('/checkout', data)
            limited_post = dict(post, request_key=field(limited_html, 'request_key'), fingerprint=field(limited_html, 'fingerprint'))
            assert request('/checkout', limited_post)[0] == 429
            db.execute('DELETE FROM order_attempts'); db.commit()
            assert db.execute('SELECT COUNT(*) FROM orders').fetchone()[0] == 1
            stranger = urllib.request.build_opener()
            assert request('/order-success', opener=stranger)[0] == 404
            assert 'Вход в админку' in request('/admin/orders', opener=stranger)[1]
            login = request('/admin/login', opener=manager)[1]
            status, _, _ = request('/admin/login', dict(csrf=field(login, 'csrf'), username='ordertest', password='QA-orders-L9!temporary-G7!only'), opener=manager)
            assert status == 200
            html = request('/admin/orders?id=1', opener=manager)[1]
            admin_csrf = field(html, 'csrf')
            base = dict(csrf=admin_csrf, version='1', action='status', status='contacted')
            assert request('/admin/orders?id=1', base, manager)[0] == 200
            assert request('/admin/orders?id=1', base, manager)[0] == 409
            assert db.execute('SELECT version FROM orders').fetchone()[0] == 2
            edit = dict(csrf=admin_csrf, version='2', action='edit', first_name='Тест', phone='+79001234567', street='Ленина', house='2', email='client@example.test', **{'quantities[americano]': '3'})
            assert request('/admin/orders?id=1', edit, manager)[0] == 200
            assert db.execute('SELECT total FROM orders').fetchone()[0] == 770
            assert 'data-order-board' in request('/admin/orders?board=1', opener=manager)[1]
            assert request('/admin/orders/move', dict(csrf=admin_csrf, id=1, version=3, status='processing'), manager)[0] == 200
            assert request('/admin/orders/count', opener=manager)[0] == 200
            with socketserver.TCPServer(('127.0.0.1', 0), SMTP) as smtp:
                threading.Thread(target=smtp.serve_forever, daemon=True).start()
                Path(directory, 'smtp.json').write_text(json.dumps(dict(host='127.0.0.1', port=smtp.server_address[1], security='none', username='', password='', **{'from': 'coffee@example.test', 'manager': 'manager@example.test'})))
                php('bin/send-order-mail.php')
                assert len(SMTP.messages) == 2
                assert db.execute("SELECT COUNT(*) FROM order_mail_queue WHERE state='sent'").fetchone()[0] == 2
                php('bin/send-order-mail.php')
                assert len(SMTP.messages) == 2
                smtp.shutdown()
            # SMTP failure leaves order intact and increments retry counter.
            db.execute("UPDATE order_mail_queue SET state='pending',next_attempt=0"); db.commit()
            Path(directory, 'smtp.json').write_text(json.dumps(dict(host='127.0.0.1', port=1, security='none', **{'from': 'coffee@example.test', 'manager': 'manager@example.test'})))
            php('bin/send-order-mail.php')
            assert db.execute('SELECT COUNT(*) FROM orders').fetchone()[0] == 1
            assert db.execute('SELECT MAX(attempts) FROM order_mail_queue').fetchone()[0] == 2
            snapshot = php('-r', 'require "app/core/bootstrap.php"; $p=createBackup(false); validateBackupDatabase($p,[]); echo $p;').strip()
            backup = Path(snapshot).read_bytes()
            restore = dict(csrf=admin_csrf, action='restore', confirm_restore=1)
            assert request('/admin/backups', restore, manager, {'backup': backup})[0] == 422
            restore['confirm_orders_rollback'] = 1
            db.close()
            restored_status, restored_html, _ = request('/admin/backups', restore, manager, {'backup': backup})
            assert restored_status == 200, re.findall(r'<p class="admin__message"[^>]*>(.*?)</p>', restored_html)
            db.close(); db = sqlite3.connect(str(Path(directory) / 'site.sqlite'))
            assert db.execute('SELECT COUNT(*) FROM order_mail_queue').fetchone()[0] == 0
            if '--serve' in sys.argv:
                print(f'QA_READY http://localhost:{PORT} user=ordertest password=QA-orders-L9!temporary-G7!only', flush=True)
                while True:
                    time.sleep(1)
            db.execute("UPDATE orders SET status='cancelled',finished_at='2026-01-01 00:00:00'"); db.commit()
            os.utime(snapshot, (time.time() - 120 * 86400, time.time() - 120 * 86400))
            php('bin/cleanup-orders.php')
            assert not Path(snapshot).exists(), 'Expired backup with contacts must be removed'
            assert db.execute('SELECT anonymized,customer FROM orders').fetchone() == (1, '{}')
            assert db.execute('SELECT COUNT(*) FROM order_events').fetchone()[0] == 0
            assert db.execute('SELECT COUNT(*) FROM order_mail_queue').fetchone()[0] == 0
            # Reintroduce the old active snapshot: the external erasure registry must clear it again.
            login = request('/admin/login', opener=manager)[1]
            request('/admin/login', dict(csrf=field(login, 'csrf'), username='ordertest', password='QA-orders-L9!temporary-G7!only'), manager)
            html = request('/admin/backups', opener=manager)[1]
            restore['csrf'] = field(html, 'csrf')
            db.close()
            restored_status, restored_html, _ = request('/admin/backups', restore, manager, {'backup': backup})
            assert restored_status == 200, re.findall(r'<p class="admin__message"[^>]*>(.*?)</p>', restored_html)
            db.close(); db = sqlite3.connect(str(Path(directory) / 'site.sqlite'))
            assert db.execute('SELECT anonymized,customer,status FROM orders').fetchone() == (1, '{}', 'cancelled')
            log.flush()
            assert 'Fatal error' not in Path(directory, 'server.log').read_text()
            print('PASS: checkout/address/CSRF/idempotency/privacy/admin/edit/conflict/board/SMTP/retries/backup/cleanup')
        finally:
            server.terminate(); server.wait(); db.close(); log.close()


if __name__ == '__main__':
    run()
