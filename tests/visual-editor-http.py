"""Изолированная приёмка визуального редактора; --serve поднимает стенд для браузера."""
from pathlib import Path
import html
import http.cookiejar
import json
import os
import re
import secrets
import sqlite3
import subprocess
import sys
import tempfile
import time
import urllib.error
import urllib.parse
import urllib.request

ROOT = Path(os.environ.get('COFFEE_TEST_ROOT', str(Path(__file__).resolve().parents[1]))).resolve()
PORT = int(os.environ.get('COFFEE_VISUAL_TEST_PORT', '8096' if '--serve' in sys.argv else '8097'))


def run():
    with tempfile.TemporaryDirectory(prefix='coffee-visual-') as directory:
        env = dict(os.environ, APP_DATA_DIR=directory, APP_UPLOAD_DIR=str(Path(directory) / 'uploads'), APP_PASSWORD_HIBP='0')
        subprocess.run(['php', 'bin/migrate.php'], cwd=ROOT, env=env, check=True, capture_output=True)
        password = secrets.token_urlsafe(24)
        subprocess.run(['php', 'bin/create-admin.php', 'visualtest'], input=password, text=True, cwd=ROOT, env=env, check=True, capture_output=True)
        modules = subprocess.check_output(['php', '-m'], text=True).lower().splitlines()
        flags = [flag for name in ['gd', 'zip'] if name not in modules for flag in ['-d', 'extension=' + name]]
        log = open(Path(directory) / 'server.log', 'w')
        server = subprocess.Popen(['php'] + flags + ['-d', 'upload_tmp_dir=' + directory, '-S', f'localhost:{PORT}', 'router.php'], cwd=ROOT, env=env, stdout=subprocess.DEVNULL, stderr=log)
        jar = http.cookiejar.CookieJar()
        client = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))
        guest = urllib.request.build_opener()

        def request(path, data=None, files=None, binary=False, opener=None):
            body = urllib.parse.urlencode(data).encode() if data is not None else None
            headers = {}
            if files:
                boundary = 'visual-' + secrets.token_hex(12)
                chunks = []
                for name, value in (data or {}).items():
                    chunks.append(f'--{boundary}\r\nContent-Disposition: form-data; name="{name}"\r\n\r\n{value}\r\n'.encode())
                for name, (filename, mime, content) in files.items():
                    chunks.append(f'--{boundary}\r\nContent-Disposition: form-data; name="{name}"; filename="{filename}"\r\nContent-Type: {mime}\r\n\r\n'.encode() + content + b'\r\n')
                chunks.append(f'--{boundary}--\r\n'.encode())
                body = b''.join(chunks)
                headers['Content-Type'] = 'multipart/form-data; boundary=' + boundary
            try:
                response = (opener or client).open(urllib.request.Request(f'http://localhost:{PORT}' + path, body, headers), timeout=10)
            except urllib.error.HTTPError as error:
                response = error
            payload = response.read()
            return response.status, payload if binary else payload.decode(), response.headers

        def csrf(page):
            return re.search(r'name="csrf" value="([a-f0-9]+)"', page).group(1)

        def state():
            status, page, headers = request('/')
            assert status == 200
            return json.loads(html.unescape(re.search(r'data-state="([^"]+)"', page).group(1)))

        try:
            for attempt in range(50):
                try:
                    login = request('/admin/login')[1]
                    break
                except urllib.error.URLError:
                    time.sleep(.1)
            if '--serve' in sys.argv:
                metadata = Path(__file__).resolve().parents[1] / 'var' / ('visual-editor-browser.json' if PORT == 8096 else f'visual-editor-browser-{PORT}.json')
                metadata.parent.mkdir(exist_ok=True)
                metadata.write_text(json.dumps({'url': f'http://localhost:{PORT}', 'username': 'visualtest', 'password': password, 'data_dir': directory}), encoding='utf-8')
                print('Browser fixture ready at http://localhost:' + str(PORT), flush=True)
                while server.poll() is None:
                    time.sleep(1)
                return

            assert request('/admin/visual-editor/save', {'csrf': 'bad', 'changes': '[]'})[0] == 401
            assert request('/admin/visual-editor/session')[0] == 401
            assert 'Вход в админку' in request('/admin/visual-editor/preview')[1]
            guest_page = request('/', opener=guest)
            assert 'data-visual-editor' not in guest_page[1] and 'data-visual-text=' not in guest_page[1]
            assert guest_page[2].get('Set-Cookie') is None
            dashboard = request('/admin/login', {'csrf': csrf(login), 'username': 'visualtest', 'password': password})[1]
            token = csrf(dashboard)
            status, shell, headers = request('/admin/visual-editor')
            assert status == 200 and "frame-ancestors 'none'" in headers['Content-Security-Policy']
            assert 'no-store' in headers['Cache-Control']
            assert 'data-visual-editor' in shell and '<iframe' not in shell
            assert 'data-site-header' in shell and 'data-visual-controls' in shell
            assert headers['Vary'] == 'Cookie'
            preview_headers = request('/admin/visual-editor/preview')[2]
            assert "frame-ancestors 'none'" in preview_headers['Content-Security-Policy']
            assert "blob:" in headers['Content-Security-Policy']
            assert "frame-ancestors 'none'" in request('/')[2]['Content-Security-Policy']
            cookie = next(cookie for cookie in jar if cookie.name == 'coffee_admin')
            assert cookie.path == '/' and cookie.has_nonstandard_attr('HttpOnly')
            # Ранее открытая админка переносит прежний сеанс /admin на весь сайт.
            old_id = cookie.value
            jar.clear(cookie.domain, cookie.path, cookie.name)
            cookie.path = '/admin'
            jar.set_cookie(cookie)
            assert 'data-visual-editor' not in request('/')[1]
            assert 'Управление кофейней' in request('/admin')[1]
            cookie = next(cookie for cookie in jar if cookie.name == 'coffee_admin')
            assert cookie.path == '/' and cookie.value == old_id
            assert len(list(jar)) == 1
            initial = state()
            assert 'copy-1' in initial['texts'] and 'copy-0' not in initial['texts'] and 'copy-5' not in initial['texts']
            assert set(initial['images']) == {'image-0', 'image-1'}
            preview = request('/admin/visual-editor/preview')[1]
            assert 'data-visual-text="copy-1"' in preview and 'data-visual-text="copy-0"' not in preview
            assert 'data-visual-href="/admin/edit?section=products&amp;id=americano"' in preview
            assert 'data-visual-text=' not in request('/', opener=guest)[1]

            def change(key, value, current=None):
                current = current or state()
                return {'kind': 'content', 'id': key, 'html': value, 'version': current['texts'][key]['version']}

            def image(key, alt, current=None):
                current = current or state()
                return {'kind': 'images', 'id': key, 'alt': alt, 'version': current['images'][key]['version']}

            def save(changes, files=None, csrf_value=None):
                return request('/admin/visual-editor/save', {'csrf': token if csrf_value is None else csrf_value, 'changes': json.dumps(changes)}, files)

            assert save([change('copy-1', 'Не должно сохраниться')], csrf_value='bad')[0] == 403
            assert save([{'kind': 'products', 'id': 'americano', 'html': 'bad', 'version': 'x'}])[0] == 422
            assert save([change('copy-0', 'bad', {'texts': {'copy-0': {'version': 'x'}}})])[0] == 422
            duplicate = change('copy-1', 'Повтор')
            assert save([duplicate, duplicate])[0] == 422
            assert save([change('copy-1', 'Не публиковать'), image('image-0', '')])[0] == 422
            assert state()['texts']['copy-1'] == initial['texts']['copy-1']
            stale = change('copy-1', 'Старая вкладка', initial)
            html_text = 'В <b onclick="bad()">кофейне</b> <i>уютно</i>, 14 дней<script>bad()</script><a href="javascript:bad()">ссылка</a><img src=x onerror=bad()>'
            assert save([change('copy-1', html_text)])[0] == 200
            saved = state()['texts']['copy-1']['html']
            assert '<strong>кофейне</strong>' in saved and '<em>уютно</em>' in saved
            assert '\u00a0' in saved and '<a' not in saved and '<script' not in saved and 'onclick' not in saved and '<img' not in saved
            assert save([stale])[0] == 409
            assert '<strong>кофейне</strong>' in request('/')[1]
            assert 'Изменить на странице' in request('/admin/edit?section=content&id=copy-1')[1]

            # Текст с HTML-подобными символами из прежней версии остаётся текстом.
            db = sqlite3.connect(str(Path(directory) / 'site.sqlite'))
            raw = '<script>old-literal</script>'
            payload = json.loads(db.execute("SELECT payload FROM records WHERE kind='content' AND id='copy-2'").fetchone()[0])
            payload['text'] = raw
            db.execute("UPDATE records SET payload=? WHERE kind='content' AND id='copy-2'", (json.dumps(payload),))
            db.commit()
            assert html.escape(raw, quote=False) in request('/')[1]
            assert raw not in request('/')[1]

            photo_bytes = (ROOT / 'assets/img/og-image.jpg').read_bytes()
            files = {'photo_image-0': ('photo.jpg', 'image/jpeg', photo_bytes), 'photo_image-1': ('bad.svg', 'image/svg+xml', b'<svg/>')}
            before = state()
            assert save([change('copy-3', 'Не публиковать'), image('image-0', 'Новое фото'), image('image-1', 'Новое фото')], files)[0] == 422
            assert state()['texts']['copy-3'] == before['texts']['copy-3']
            assert not list((Path(directory) / 'uploads').glob('*'))
            assert save([change('copy-3', 'Выбрать кофе'), image('image-0', 'Фотография для теста')], {'photo_image-0': ('photo.jpg', 'image/jpeg', photo_bytes)})[0] == 200
            new_image = state()['images']['image-0']
            assert new_image['src'] != before['images']['image-0']['src']
            assert new_image['alt'] == 'Фотография для теста'
            assert len(list((Path(directory) / 'uploads').glob('*.webp'))) == 2
            assert request(new_image['src'], binary=True)[1].startswith(b'RIFF')
            assert request(new_image['src'].replace('.webp', '@2x.webp'), binary=True)[0] == 200
            assert request('/admin/edit?section=images&id=image-0', {'csrf': token, 'record_version': before['images']['image-0']['version'], 'name': 'Фото', 'image': new_image['src'].lstrip('/'), 'alt': 'bad'})[0] == 409

            # Старые копии без новых фиксированных ключей остаются допустимыми.
            for key in ['space-fact-1', 'space-fact-2', 'space-fact-3', 'reviews-title']:
                db.execute("DELETE FROM records WHERE kind='content' AND id=?", (key,))
            db.commit()
            assert 'Столики для встреч' in request('/')[1]
            archive = request('/admin/backups', {'csrf': token, 'action': 'export-db'}, binary=True)[1]
            assert archive.startswith(b'SQLite format 3')
            db.close()
            assert request('/admin/backups', {'csrf': token, 'action': 'restore', 'confirm_restore': 1}, {'backup': ('old.sqlite', 'application/octet-stream', archive)})[0] == 200
            assert '<strong>кофейне</strong>' in request('/')[1] and 'Столики для встреч' in request('/')[1]
            assert request('/admin/visual-editor/session')[0] == 401
            assert 'data-visual-editor' not in request('/')[1]
            print('PASS: on-site editor, guest isolation/no cookie, session path migration/revocation, auth/CSRF/CSP, whitelist, inline HTML/typography, atomic text/photos, WebP/retina, conflicts, plain legacy text, old backups')
        finally:
            server.terminate()
            server.wait(timeout=5)
            log.close()
            if sys.exc_info()[0]:
                print((Path(directory) / 'server.log').read_text(encoding='utf-8', errors='replace')[-4000:])


if __name__ == '__main__':
    run()
