import os
import ssl
import urllib.request
import socket

# Force IPv4
old_getaddrinfo = socket.getaddrinfo
def new_getaddrinfo(*args, **kwargs):
    responses = old_getaddrinfo(*args, **kwargs)
    return [r for r in responses if r[0] == socket.AF_INET]
socket.getaddrinfo = new_getaddrinfo

ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

REST_ID = "334fab4b319e125f"
AUTH_KEY = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyIjp7ImlkIjoxLCJsb2NhbGUiOiJlbl9VUyIsInZpZXdNb2RlIjoibGlzdCIsInNpbmdsZUNsaWNrIjpmYWxzZSwicmVkaXJlY3RBZnRlckNvcHlNb3ZlIjpmYWxzZSwicGVybSI6eyJhZG1pbiI6ZmFsc2UsImV4ZWN1dGUiOmZhbHNlLCJjcmVhdGUiOnRydWUsInJlbmFtZSI6dHJ1ZSwibW9kaWZ5Ijp0cnVlLCJkZWxldGUiOnRydWUsInNoYXJlIjpmYWxzZSwiZG93bmxvYWQiOnRydWV9LCJjb21tYW5kcyI6W10sImxvY2tQYXNzd29yZCI6dHJ1ZSwiaGlkZURvdGZpbGVzIjpmYWxzZSwiZGF0ZUZvcm1hdCI6ZmFsc2UsInVzZXJuYW1lIjoidTYxMTgyNDMwMSIsImFjZUVkaXRvclRoZW1lIjoiIn0sImlzcyI6IkZpbGUgQnJvd3NlciIsImV4cCI6MTc5MTU5MjI0NCwiaWF0IjoxNzkxNTcwNjQ0fQ.OmlkknudBSTjfi_ca4oF1grgWP2o1ensaekIyia5RSU"
REST_AUTH_KEY = "74aa54f8f635c6fc50bfa33ae56d9d432b64f96a498ddfc0ce22864c6e99b089-334fab4b319e125f"

URL_TUS = f"https://srv807-files.hstgr.io/rest/{REST_ID}/api/tus/public_html"
URL_RES = f"https://srv807-files.hstgr.io/rest/{REST_ID}/api/resources/public_html"

SRC_DIR = r"C:\Users\DELL\editorialtuco"

DIRS_TO_CREATE = ["admin", "assets", "data", "inc", "uploads"]

FILES_TO_DEPLOY = [
    ".htaccess",
    "index.php",
    "noticia.php",
    "categoria.php",
    "buscar.php",
    "404.php",
    "arrepentimiento.php",
    "legales.php",
    "privacidad.php",
    "terminos.php",
    "robots.txt",
    "rss.php",
    "rss.xml",
    "sitemap.php",
    "sitemap.xml",
    "llms.txt",
    "llms-full.txt",
    "DESIGN.md",
    "admin/_head.php",
    "admin/categorias.php",
    "admin/editar.php",
    "admin/eliminar.php",
    "admin/index.php",
    "admin/login.php",
    "admin/logout.php",
    "assets/admin-editor.css",
    "assets/favicon.png",
    "assets/logo-editorial-tuco.png",
    "assets/logo-editorial-tuco-dark.png",
    "assets/style.css",
    "data/.htaccess",
    "data/tramites_consumidor.json",
    "inc/config.php",
    "inc/footer.php",
    "inc/header.php",
]

# Add uploads
uploads_dir = os.path.join(SRC_DIR, "uploads")
if os.path.exists(uploads_dir):
    for up_file in os.listdir(uploads_dir):
        if not up_file.startswith("."):
            FILES_TO_DEPLOY.append(f"uploads/{up_file}")

def create_dirs():
    print("Creando directorios remotos...")
    for d in DIRS_TO_CREATE:
        url = f"{URL_RES}/{d}/?override=false"
        req = urllib.request.Request(
            url,
            data=b"",
            headers={"X-Auth": AUTH_KEY, "X-Auth-Rest": REST_AUTH_KEY},
            method="POST"
        )
        try:
            with urllib.request.urlopen(req, context=ctx, timeout=10) as r:
                print(f"Directorio {d}: OK (HTTP {r.status})")
        except urllib.error.HTTPError as e:
            if e.code in (409, 400):
                print(f"Directorio {d}: ya existe.")
            else:
                print(f"Directorio {d}: HTTP {e.code}")

def delete_default_page():
    print("Eliminando default.php si existe...")
    url = f"{URL_RES}/default.php"
    req = urllib.request.Request(
        url,
        headers={"X-Auth": AUTH_KEY, "X-Auth-Rest": REST_AUTH_KEY},
        method="DELETE"
    )
    try:
        with urllib.request.urlopen(req, context=ctx, timeout=10) as r:
            print(f"default.php eliminado: HTTP {r.status}")
    except Exception as e:
        print(f"default.php no eliminado o ya ausente: {e}")

def upload_file(rel_path):
    full_path = os.path.join(SRC_DIR, rel_path.replace("/", os.sep))
    if not os.path.exists(full_path):
        print(f"[SKIP] No existe local: {rel_path}")
        return

    with open(full_path, "rb") as f:
        data = f.read()
    size = len(data)
    target_url = f"{URL_TUS}/{rel_path}?override=true"

    # 1. POST (Tus create)
    req_post = urllib.request.Request(
        target_url,
        headers={
            "X-Auth": AUTH_KEY,
            "X-Auth-Rest": REST_AUTH_KEY,
            "Tus-Resumable": "1.0.0",
            "Upload-Length": str(size),
            "Upload-Offset": "0"
        },
        method="POST"
    )
    try:
        with urllib.request.urlopen(req_post, context=ctx, timeout=15) as r:
            if r.status not in (200, 201):
                print(f"[FAIL POST] {rel_path}: HTTP {r.status}")
                return
    except Exception as e:
        print(f"[ERROR POST] {rel_path}: {e}")
        return

    # 2. PATCH (Tus upload data)
    req_patch = urllib.request.Request(
        target_url,
        data=data,
        headers={
            "X-Auth": AUTH_KEY,
            "X-Auth-Rest": REST_AUTH_KEY,
            "Tus-Resumable": "1.0.0",
            "Content-Type": "application/offset+octet-stream",
            "Upload-Offset": "0"
        },
        method="PATCH"
    )
    try:
        with urllib.request.urlopen(req_patch, context=ctx, timeout=30) as r:
            offset = r.headers.get("Upload-Offset")
            if r.status in (200, 204) and int(offset) == size:
                print(f"[OK] {rel_path} ({size:,} bytes)")
            else:
                print(f"[WARN] {rel_path}: status {r.status}, offset {offset}")
    except Exception as e:
        print(f"[ERROR PATCH] {rel_path}: {e}")

def main():
    print(f"Iniciando despliegue de {len(FILES_TO_DEPLOY)} archivos a editorialtuco.com ...")
    create_dirs()
    delete_default_page()
    for rel in FILES_TO_DEPLOY:
        upload_file(rel)
    print("Despliegue finalizado exitosamente.")

if __name__ == "__main__":
    main()
