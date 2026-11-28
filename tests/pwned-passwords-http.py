"""Проверка включённого HIBP при недоступной сети; пароль никуда не передаётся."""
from pathlib import Path
import http.cookiejar
import os
import re
import secrets
import sqlite3
import subprocess
import tempfile
import time
import urllib.error
import urllib.parse
import urllib.request

ROOT = Path(os.environ.get('COFFEE_TEST_ROOT', str(Path(__file__).resolve().parents[1]))).resolve()
PORT = int(os.environ.get('COFFEE_PWNED_TEST_PORT', '8100'))

with tempfile.TemporaryDirectory(prefix='coffee-pwned-') as directory:
    setup_env = dict(os.environ, APP_DATA_DIR=directory, APP_UPLOAD_DIR=str(Path(directory) / 'uploads'), APP_PASSWORD_HIBP='0')
    subprocess.run(['php', 'bin/migrate.php'], cwd=ROOT, env=setup_env, check=True, capture_output=True)
    current = secrets.token_urlsafe(24)
    candidate = secrets.token_urlsafe(32)
    subprocess.run(['php', 'bin/create-admin.php', 'pwnedtest'], input=current, text=True, cwd=ROOT, env=setup_env, check=True, capture_output=True)
    with sqlite3.connect(str(Path(directory) / 'site.sqlite')) as database:
        initial_hash = database.execute('SELECT password_hash FROM admins WHERE id=1').fetchone()[0]
    database.close()
    online_env = dict(setup_env, APP_PASSWORD_HIBP='1')
    log = open(Path(directory) / 'server.log', 'w')
    server = subprocess.Popen(['php', '-d', 'allow_url_fopen=0', '-S', f'localhost:{PORT}', 'router.php'], cwd=ROOT, env=online_env, stdout=subprocess.DEVNULL, stderr=log)
    client = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))

    def request(path, fields=None):
        body = urllib.parse.urlencode(fields).encode() if fields is not None else None
        try:
            response = client.open(f'http://localhost:{PORT}' + path, body, timeout=10)
        except urllib.error.HTTPError as error:
            response = error
        return response.status, response.read().decode()

    def csrf(page):
        return re.search(r'name="csrf" value="([a-f0-9]+)"', page).group(1)

    try:
        for attempt in range(50):
            try:
                login = request('/admin/login')[1]
                break
            except urllib.error.URLError:
                time.sleep(.1)
        assert request('/admin/login', dict(csrf=csrf(login), username='pwnedtest', password=current))[0] == 200
        form = request('/admin/password')[1]
        assert 'Сервис получает только часть хеша' in form
        fields = dict(csrf=csrf(form), current_password=current, new_password=candidate, confirm_password=candidate)
        assert 'Текущий пароль указан неверно' in request('/admin/password', dict(fields, current_password='incorrect'))[1]
        assert 'Пароли не совпадают' in request('/admin/password', dict(fields, confirm_password='mismatch'))[1]
        status, failed = request('/admin/password', fields)
        assert status == 422 and 'Проверка утечек временно недоступна' in failed
        assert request('/admin/password')[0] == 200
        rejected = subprocess.run(['php', '-d', 'allow_url_fopen=0', 'bin/create-admin.php', 'pwnedtest', '--reset'], input=candidate, text=True, cwd=ROOT, env=online_env, capture_output=True, encoding='utf-8')
        assert rejected.returncode == 1 and 'недоступна' in rejected.stderr
        with sqlite3.connect(str(Path(directory) / 'site.sqlite')) as database:
            assert database.execute('SELECT password_hash FROM admins WHERE id=1').fetchone()[0] == initial_hash
        database.close()
        assert request('/admin/password')[0] == 200, 'Failed CLI reset must preserve the session'
        print('PASS: enabled HIBP status, ordinary sign-in, validation order, fail-closed HTTP/CLI and unchanged hash/session with no network')
    finally:
        server.terminate()
        server.wait(timeout=5)
        log.close()
