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

URL_BASE = "https://srv952-files.hstgr.io/rest/e57ad4d2a3a074df/api/tus/public_html"
AUTH_KEY = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyIjp7ImlkIjoxLCJsb2NhbGUiOiJlbl9VUyIsInZpZXdNb2RlIjoibGlzdCIsInNpbmdsZUNsaWNrIjpmYWxzZSwicmVkaXJlY3RBZnRlckNvcHlNb3ZlIjpmYWxzZSwicGVybSI6eyJhZG1pbiI6ZmFsc2UsImV4ZWN1dGUiOmZhbHNlLCJjcmVhdGUiOnRydWUsInJlbmFtZSI6dHJ1ZSwibW9kaWZ5Ijp0cnVlLCJkZWxldGUiOnRydWUsInNoYXJlIjpmYWxzZSwiZG93bmxvYWQiOnRydWV9LCJjb21tYW5kcyI6W10sImxvY2tQYXNzd29yZCI6dHJ1ZSwiaGlkZURvdGZpbGVzIjpmYWxzZSwiZGF0ZUZvcm1hdCI6ZmFsc2UsInVzZXJuYW1lIjoidTcxOTY0Nzk1NyIsImFjZUVkaXRvclRoZW1lIjoiIn0sImlzcyI6IkZpbGUgQnJvd3NlciIsImV4cCI6MTc5MTU2OTg0OCwiaWF0IjoxNzkxNTQ4MjQ4fQ.kGzDv5N_RxHeicWpK2kcIRW5F9VIA26xAaaZ96XiaeQ"
REST_AUTH_KEY = "0bfa20f55d5f8b4094632c9142f93a70667a094b44b0f2f38e854cabdeb51d6f-e57ad4d2a3a074df"

FILES = [
    "admin/_head.php",
    "admin/login.php",
    "admin/index.php",
    "admin/editar.php",
    "admin/categorias.php",
    "assets/admin-editor.css",
    "assets/style.css",
    "inc/config.php",
    "noticia.php",
    "index.php",
    "categoria.php",
    "buscar.php",
    "DESIGN.md"
]

SRC_DIR = r"C:\Users\DELL\editorialtuco"

def deploy():
    print(f"Deploying {len(FILES)} files to editorialtuco.com ...")
    for fpath in FILES:
        full_path = os.path.join(SRC_DIR, fpath.replace("/", os.sep))
        with open(full_path, "rb") as f:
            data = f.read()
        size = len(data)
        target_url = f"{URL_BASE}/{fpath}?override=true"

        # 1. POST create
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
        with urllib.request.urlopen(req_post, context=ctx, timeout=15) as r:
            if r.status not in (200, 201):
                raise RuntimeError(f"POST failed for {fpath}: {r.status}")

        # 2. PATCH data
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
        with urllib.request.urlopen(req_patch, context=ctx, timeout=30) as r:
            offset = r.headers.get("Upload-Offset")
            if r.status == 204 and int(offset) == size:
                print(f"[OK] {fpath} ({size:,} bytes)")
            else:
                print(f"[WARN] {fpath}: status {r.status}, offset {offset}")

if __name__ == "__main__":
    deploy()
