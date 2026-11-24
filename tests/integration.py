"""Изолированные HTTP-проверки. Рабочая БД и фотографии не изменяются."""
import http.cookiejar
import os
from pathlib import Path
import re
import secrets
import subprocess
import tempfile
import time
import urllib.error
import urllib.parse
import urllib.request

ROOT = Path(__file__).resolve().parents[1]


def run():
    with tempfile.TemporaryDirectory(prefix="coffee-test-") as directory:
        env = dict(os.environ, APP_DATA_DIR=directory, APP_UPLOAD_DIR=str(Path(directory) / "uploads"))
        subprocess.run(["php", "bin/migrate.php"], cwd=ROOT, env=env, check=True, capture_output=True)
        password = secrets.token_urlsafe(24)
        subprocess.run(["php", "bin/create-admin.php", "testadmin"], input=password, text=True, cwd=ROOT, env=env, check=True, capture_output=True)
        process = subprocess.Popen(["php", "-d", "extension=gd", "-d", "extension=zip", "-S", "localhost:8093", "router.php"], cwd=ROOT, env=env, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
        jar = http.cookiejar.CookieJar()
        client = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))

        def request(path, data=None):
            body = urllib.parse.urlencode(data).encode() if data is not None else None
            try:
                response = client.open("http://localhost:8093" + path, body, timeout=5)
            except urllib.error.HTTPError as error:
                response = error
            return response.status, response.read().decode(), response.headers

        def token(html):
            return re.search(r'name="csrf" value="([a-f0-9]+)"', html).group(1)

        try:
            for attempt in range(40):
                try:
                    status, html, headers = request("/admin")
                    break
                except urllib.error.URLError:
                    time.sleep(.1)
            assert status == 200 and "Вход в админку" in html
            csrf = token(html)
            assert request("/admin/login", {"csrf": "wrong", "username": "testadmin", "password": password})[0] == 403
            status, html, _ = request("/admin/login", {"csrf": csrf, "username": "testadmin", "password": password})
            assert status == 200 and "Управление кофейней" in html
            assert token(html) != csrf, "CSRF rotates after login"
            assert request("/config/seed.json")[0] == 404
            assert request("/var/site.sqlite")[0] == 404
            assert request("/app/core/bootstrap.php")[0] == 404
            print("PASS: isolated migration, login, CSRF rotation, private paths")
        finally:
            process.terminate()
            process.wait(timeout=5)


if __name__ == "__main__":
    run()
