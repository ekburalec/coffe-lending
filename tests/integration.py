"""Изолированные HTTP-проверки. Рабочая БД и фотографии не изменяются."""
import http.cookiejar
import os
from pathlib import Path
import re
import secrets
import sqlite3
import json
import io
import zipfile
import subprocess
import tempfile
import time
import urllib.error
import urllib.parse
import urllib.request

ROOT = Path(os.environ.get("COFFEE_TEST_ROOT", str(Path(__file__).resolve().parents[1]))).resolve()


def run():
    with tempfile.TemporaryDirectory(prefix="coffee-test-") as directory:
        env = dict(os.environ, APP_DATA_DIR=directory, APP_UPLOAD_DIR=str(Path(directory) / "uploads"))
        subprocess.run(["php", "bin/migrate.php"], cwd=ROOT, env=env, check=True, capture_output=True)
        password = secrets.token_urlsafe(24)
        subprocess.run(["php", "bin/create-admin.php", "testadmin"], input=password, text=True, cwd=ROOT, env=env, check=True, capture_output=True)
        modules = subprocess.check_output(["php", "-m"], text=True).lower().splitlines()
        extension_flags = [flag for name in ["gd", "zip"] if name not in modules for flag in ["-d", "extension=" + name]]
        process = subprocess.Popen(["php"] + extension_flags + [ "-d", "display_errors=0", "-d", "upload_tmp_dir=" + directory, "-S", "localhost:8093", "router.php"], cwd=ROOT, env=env, stdout=subprocess.DEVNULL, stderr=open(Path(directory) / "server.log", "w"))
        jar = http.cookiejar.CookieJar()
        client = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))

        def request(path, data=None, files=None, raw=False):
            body = urllib.parse.urlencode(data).encode() if data is not None else None
            headers = {}
            if files:
                boundary = "coffee-" + secrets.token_hex(12)
                chunks = []
                for name, value in (data or {}).items():
                    chunks.append(f'--{boundary}\r\nContent-Disposition: form-data; name="{name}"\r\n\r\n{value}\r\n'.encode())
                for name, (filename, mime, content) in files.items():
                    chunks.append(f'--{boundary}\r\nContent-Disposition: form-data; name="{name}"; filename="{filename}"\r\nContent-Type: {mime}\r\n\r\n'.encode() + content + b"\r\n")
                chunks.append(f"--{boundary}--\r\n".encode())
                body = b"".join(chunks)
                headers["Content-Type"] = "multipart/form-data; boundary=" + boundary
            req = urllib.request.Request("http://localhost:8093" + path, body, headers)
            try:
                response = client.open(req, timeout=5)
            except urllib.error.HTTPError as error:
                response = error
            content = response.read()
            return response.status, content if raw else content.decode(), response.headers

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
            csrf = token(html)
            db = sqlite3.connect(str(Path(directory) / "site.sqlite"))
            assert db.execute("SELECT count(*) FROM records WHERE kind='products'").fetchone()[0] == 9
            assert db.execute('SELECT MAX(version) FROM schema_version').fetchone()[0] == 2
            assert 'href="#reviews"' in request('/')[1]
            assert 'Отзывов пока нет.' in request('/')[1]
            status, html, _ = request("/admin/edit?section=categories&new=1", {"csrf": csrf, "name": "Тест", "sort_order": 4, "visible": 1})
            assert status == 200 and "Изменения сохранены" in html
            editor = request("/admin/edit?section=products")[1]
            assert 'data-reorder-list' in editor and 'Выше</button>' not in editor and 'Ниже</button>' not in editor
            version = re.search(r'data-reorder-category="coffee" data-reorder-version="([a-f0-9]+)"', editor).group(1)
            coffee = [r[0] for r in db.execute("SELECT id FROM records WHERE kind='products' AND json_extract(payload,'$.category')='coffee' ORDER BY sort_order,id")]
            ordered = list(reversed(coffee))
            move = {"csrf": csrf, "category": "coffee", "version": version, "ids": json.dumps(ordered)}
            assert request("/admin/products/reorder", dict(move, csrf="bad"))[0] == 403
            assert request("/admin/products/reorder", dict(move, ids=json.dumps([coffee[0]] * len(coffee))))[0] == 422
            assert request("/admin/products/reorder", dict(move, ids=json.dumps(["black-tea"] + ordered[1:])))[0] == 422
            status, result, _ = request("/admin/products/reorder", move)
            assert status == 200, result
            assert [r[0] for r in db.execute("SELECT id FROM records WHERE kind='products' AND json_extract(payload,'$.category')='coffee' ORDER BY sort_order,id")] == ordered
            landing_order = re.findall(r'data-product="([^"]+)"', request("/")[1])[:len(coffee)]
            assert landing_order == ordered
            assert request("/admin/products/reorder", move)[0] == 409
            assert [r[0] for r in db.execute("SELECT id FROM records WHERE kind='products' AND json_extract(payload,'$.category')='coffee' ORDER BY sort_order,id")] == ordered
            category_id = db.execute("SELECT id FROM records WHERE kind='categories' AND json_extract(payload, '$.name')='Тест'").fetchone()[0]
            product = {"csrf": csrf, "name": "Новый напиток", "category": category_id, "description": "Вкус", "ingredients": "Вода", "portion": "200 мл", "price": 125, "available": 1, "visible": 1, "sort_order": 1, "alt": "Чашка", "image": ""}
            file = (ROOT / "assets/img/menu/photo_1.webp").read_bytes()
            status, html, _ = request("/admin/edit?section=products&new=1", product, {"image_upload": ("drink.webp", "image/webp", file)})
            assert status == 200 and "Изменения сохранены" in html, html[:200]
            product_id, payload = db.execute("SELECT id,payload FROM records WHERE kind='products' AND json_extract(payload, '$.name')='Новый напиток'").fetchone()
            image = json.loads(payload)["image"]
            assert (Path(directory) / "uploads" / Path(image).name).is_file()
            assert (Path(directory) / "uploads" / Path(image).name.replace(".webp", "@2x.webp")).is_file()
            product["image"] = image
            product["price"] = -1
            assert request("/admin/edit?section=products&id=" + product_id, product)[0] == 422
            assert request("/admin/edit?section=categories&id=" + category_id, {"csrf": csrf, "action": "delete", "confirm_delete": 1})[0] == 422
            product["price"] = 125
            assert request("/admin/edit?section=products&new=1", product, {"image_upload": ("bad.svg", "image/svg+xml", b"<svg/>")})[0] == 422
            contacts = {"csrf": csrf, "name": "Тест кофейня", "city": "Екатеринбург", "street": "Новый адрес", "phone": "+70000000000", "map_url": "javascript:alert(1)"}
            assert request("/admin/edit?section=contacts", contacts)[0] == 422
            contacts["map_url"] = "https://example.com/map"
            assert request("/admin/edit?section=contacts", contacts)[0] == 200
            assert request("/admin/edit?section=hours&id=1", {"csrf": csrf, "opens": "22:00", "closes": "08:00"})[0] == 422
            special = {"csrf": csrf, "date": "2026-12-31", "closed": 1, "note": "Праздник", "visible": 1, "sort_order": 0}
            assert request("/admin/edit?section=exceptions&new=1", special)[0] == 200
            assert request("/admin/edit?section=exceptions&new=1", special)[0] == 422
            delivery = {"csrf": csrf, "fee": 200, "threshold": 1200, "zone": "Рядом", "pickup_time": "20 минут", "delivery_time": "50 минут"}
            assert request("/admin/edit?section=delivery", delivery)[0] == 422
            delivery["delivery_enabled"] = 1
            assert request("/admin/edit?section=delivery", delivery)[0] == 200
            delivery["threshold"] = "12.5"
            assert request("/admin/edit?section=delivery", delivery)[0] == 422
            for section in ["navigation", "content", "images", "faq", "announcements", "reviews", "rating"]:
                assert request("/admin/edit?section=" + section)[0] == 200, section
            announcement = {"csrf": csrf, "text": "Праздничный график", "starts": "2026-12-31T08:00", "ends": "2026-12-31T20:00", "sort_order": 0, "visible": 1}
            assert request("/admin/edit?section=announcements&new=1", announcement)[0] == 200
            announcement["ends"] = "2026-12-30T20:00"
            assert request("/admin/edit?section=announcements&new=1", announcement)[0] == 422
            assert request("/admin/edit?section=rating", {"csrf": csrf, "value": "4.9", "count": 100, "source": ""})[0] == 422
            assert request("/admin/edit?section=navigation&new=1", {"csrf": csrf, "name": "Плохой пункт", "target": "#missing"})[0] == 422
            status, landing, _ = request("/")
            assert status == 200
            assert "Новый напиток" in landing and "Новый адрес" in landing and "Тест кофейня" in landing
            assert 'data-delivery-fee="200"' in landing and 'data-free-delivery-from="1200"' in landing
            assert 'value="pickup"' not in landing and 'value="delivery" checked' in landing
            assert landing.count('data-product=') == 10 and landing.count('data-order-row=') == 10
            code = "require 'app/core/bootstrap.php'; echo json_encode([openingHours(new DateTimeImmutable('2026-12-31T10:00:00+05:00')), count(activeAnnouncements(new DateTimeImmutable('2026-12-31T08:00:00+05:00'))), count(activeAnnouncements(new DateTimeImmutable('2026-12-31T20:00:00+05:00'))), count(activeAnnouncements(new DateTimeImmutable('2026-12-31T20:01:00+05:00')))]);"
            result = subprocess.run(["php", "-r", code], cwd=ROOT, env=env, check=True, capture_output=True)
            hours, begin, end, after = json.loads(result.stdout)
            assert hours["special"] and "закрыто" in hours["today"]
            assert [begin, end, after] == [1, 1, 0]
            product["price"] = 125
            product.pop("available")
            assert request("/admin/edit?section=products&id=" + product_id, product)[0] == 200
            landing = request("/")[1]
            assert landing.count('data-product=') == 10 and landing.count('data-order-row=') == 9 and "Нет в наличии" in landing
            assert request("/admin/edit?section=navigation&new=1", {"csrf": csrf, "name": "Помощь", "target": "#faq", "sort_order": 9, "visible": 1})[0] == 200
            assert request("/admin/edit?section=content&id=copy-1", {"csrf": csrf, "text": "Тестовый заголовок <script>bad</script>"})[0] == 200
            assert request("/admin/edit?section=faq&new=1", {"csrf": csrf, "question": "Тестовый вопрос", "mode": "manual", "prefix": "", "answer": "Тестовый ответ", "sort_order": 10, "visible": 1})[0] == 200
            assert request("/admin/edit?section=reviews&new=1", {"csrf": csrf, "name": "Автор теста", "text": "Тестовый отзыв", "source": "https://example.com/review", "sort_order": 0, "visible": 1})[0] == 200
            assert request("/admin/edit?section=rating", {"csrf": csrf, "value": "4.9", "count": 10, "source": "https://example.com/rating"})[0] == 200
            assert request("/admin/edit?section=announcements&new=1", {"csrf": csrf, "text": "Объявление без дат", "starts": "", "ends": "", "sort_order": 0, "visible": 1})[0] == 200
            landing = request("/")[1]
            for value in ["Помощь", "Тестовый заголовок", "Тестовый вопрос", "Тестовый ответ", "Тестовый отзыв", "4.9", "Объявление без дат"]:
                assert value in landing, value
            assert '<script>bad</script>' not in landing
            assert 'data-reviews-track' in landing and landing.index('id="reviews"') < landing.index('id="faq"')
            review_id = db.execute("SELECT id FROM records WHERE kind='reviews'").fetchone()[0]
            review_url = "/admin/edit?section=reviews&id=" + review_id
            assert request(review_url, {"csrf": "bad", "action": "toggle_visibility"})[0] == 403
            assert request("/admin/edit?section=navigation&new=1", {"csrf": csrf, "name": "Отзывы", "target": "#reviews", "sort_order": 10, "visible": 1})[0] == 200
            assert 'href="#reviews"' in request("/")[1]
            status, review_editor, _ = request(review_url, {"csrf": csrf, "action": "toggle_visibility"})
            assert status == 200 and "Скрыто" in review_editor and "Показать" in review_editor
            empty_reviews = request("/")[1]
            assert 'id="reviews"' in empty_reviews and 'href="#reviews"' in empty_reviews and 'Отзывов пока нет.' in empty_reviews
            assert request(review_url, {"csrf": csrf, "action": "toggle_visibility"})[0] == 200
            assert 'id="reviews"' in request("/")[1]
            assert request(review_url, {"csrf": csrf, "action": "delete"})[0] == 422
            assert request(review_url, {"csrf": csrf, "action": "delete", "confirm_delete": 1})[0] == 200
            inline_review = {"csrf": csrf, "return_to": "list", "name": "Автор теста", "text": "Тестовый отзыв", "source": "https://example.com/review", "sort_order": 0, "visible": 1}
            status, inline_html, _ = request("/admin/edit?section=reviews", inline_review)
            assert status == 200 and 'id="add-record"' in inline_html and 'id="rating-title"' in inline_html
            assert 'id="rating-source"' in inline_html and 'id="field-source"' in inline_html
            new_faq_id = db.execute("SELECT id FROM records WHERE kind='faq' AND json_extract(payload,'$.question')='Тестовый вопрос'").fetchone()[0]
            assert request("/admin/edit?section=faq&id=" + new_faq_id, {"csrf": csrf, "action": "toggle_visibility"})[0] == 200
            assert "Тестовый вопрос" not in request("/")[1]
            assert 'id="add-record"' in request("/admin/edit?section=faq")[1]
            visible_faq_ids = [r[0] for r in db.execute("SELECT id FROM records WHERE kind='faq' AND visible=1")]
            for faq_id in visible_faq_ids:
                assert request("/admin/edit?section=faq&id=" + faq_id, {"csrf": csrf, "action": "toggle_visibility"})[0] == 200
            no_faq = request("/")[1]
            assert 'id="faq"' not in no_faq and 'href="#faq"' not in no_faq
            for faq_id in visible_faq_ids:
                assert request("/admin/edit?section=faq&id=" + faq_id, {"csrf": csrf, "action": "toggle_visibility"})[0] == 200
            assert request("/admin/edit?section=categories&id=coffee", {"csrf": csrf, "name": "Кофе", "sort_order": 0})[0] == 200
            assert 'data-product="americano"' not in request("/")[1]
            assert request("/admin/edit?section=categories&id=coffee", {"csrf": csrf, "name": "Кофе", "sort_order": 0, "visible": 1})[0] == 200
            seo = {"csrf": csrf, "title": "Новое название", "description": "Описание <script>alert(1)</script>", "og_title": "Превью", "og_description": "Описание превью", "image": "assets/img/og-image.jpg"}
            assert request("/admin/edit?section=seo", seo)[0] == 200
            landing = request("/")[1]
            assert "<title>Новое название</title>" in landing
            assert '<script>alert(1)</script>' not in landing
            graph = json.loads(re.search(r'<script type="application/ld\+json">(.*?)</script>', landing, re.S).group(1))["@graph"]
            assert graph[0]["address"]["streetAddress"] == "Новый адрес"
            assert graph[0]["hasMenu"]["hasMenuSection"][-1]["hasMenuItem"][0]["offers"]["price"] == 125
            faq_schema = [x["acceptedAnswer"]["text"] for x in graph[1]["mainEntity"]]
            assert any("200" in text and "1 200" in text for text in faq_schema)
            assert request("/" + image, raw=True)[1].startswith(b"RIFF")
            assert "Новый адрес" in request("/llms-full.txt")[1]
            assert json.loads(request("/manifest.webmanifest")[1])["name"] == "Тест кофейня"
            db.execute("DELETE FROM records WHERE kind='content' AND id='faq-title'")
            db.commit()
            subprocess.run(["php", "bin/migrate.php"], cwd=ROOT, env=env, check=True, capture_output=True)
            assert db.execute("SELECT count(*) FROM records WHERE kind='content' AND id='faq-title'").fetchone()[0] == 1
            assert "Новое название" in request("/")[1]
            assert "Disallow: /" in request("/robots.txt")[1]
            assert request("/sitemap.xml")[0] == 404
            assert request("/admin/edit?section=hours&id=1", {"csrf": csrf, "opens": "09:00", "closes": "21:00"})[0] == 200
            landing = request("/")[1]
            footer = landing[landing.index('<footer'):]
            assert "09:00–21:00" in footer
            db.close()
            status, sqlite_bytes, headers = request("/admin/backups", {"csrf": csrf, "action": "export-db"}, raw=True)
            assert status == 200 and sqlite_bytes.startswith(b"SQLite format 3")
            assert request("/admin/backups", {"csrf": csrf, "action": "restore", "confirm_restore": 1}, {"backup": ("bad.sqlite", "application/octet-stream", b"invalid database")})[0] == 422
            status, archive, headers = request("/admin/backups", {"csrf": csrf, "action": "export-full"}, raw=True)
            assert status == 200 and headers["Content-Type"] == "application/zip"
            with zipfile.ZipFile(io.BytesIO(archive)) as z:
                assert "site.sqlite" in z.namelist() and "manifest.json" in z.namelist()
                assert len([n for n in z.namelist() if n.startswith("media/")]) == 2
            evil = io.BytesIO()
            with zipfile.ZipFile(evil, "w") as z: z.writestr("../escape.txt", "bad")
            assert request("/admin/backups", {"csrf": csrf, "action": "restore", "confirm_restore": 1}, {"backup": ("bad.zip", "application/zip", evil.getvalue())})[0] == 422
            contacts["street"] = "После копии"
            assert request("/admin/edit?section=contacts", contacts)[0] == 200
            for photo in (Path(directory) / "uploads").iterdir(): photo.unlink()
            status, html, _ = request("/admin/backups", {"csrf": csrf, "action": "restore", "confirm_restore": 1}, {"backup": ("full.zip", "application/zip", archive)})
            assert status == 200 and "Копия восстановлена" in html, re.findall(r'<p class="admin__message"[^>]*>(.*?)</p>', html)
            assert "Новый адрес" in request("/")[1] and "После копии" not in request("/")[1]
            assert len(list((Path(directory) / "uploads").iterdir())) == 2
            assert len(list((Path(directory) / "backups").glob("*.zip"))) == 2
            login_html = request("/admin")[1]
            assert "Вход в админку" in login_html
            assert request("/admin/products/reorder", {"csrf": "old", "category": "coffee", "version": version, "ids": json.dumps(ordered)})[0] == 401
            csrf_login = token(login_html)
            for attempt in range(5):
                assert request("/admin/login", {"csrf": csrf_login, "username": "testadmin", "password": "wrong"})[0] == 422
            assert request("/admin/login", {"csrf": csrf_login, "username": "testadmin", "password": password})[0] == 429
            print("PASS: isolated migration, login, CSRF, private paths, catalog CRUD, WebP/retina, validation, dates, SEO, backup/restore")
        finally:
            if "db" in locals(): db.close()
            process.terminate()
            process.wait(timeout=5)
            if __import__("sys").exc_info()[0]: print((Path(directory) / "server.log").read_text(encoding="utf-8", errors="replace")[-3500:])


if __name__ == "__main__":
    run()
