#!/usr/bin/env python3
"""Sube archivos por TUS a Hostinger (rutas relativas a public_html de liaa.cloud).

Uso:
    python3 subir.py                 # sube todo ~/workspace/demo-portal-noticias -> caro/
    python3 subir.py index.php inc/config.php   # sube solo esos archivos
"""
import json, os, subprocess, sys, urllib.request

SRC = os.path.expanduser("~/workspace/demo-portal-noticias")
PREFIX = "caro"
USERNAME = "u719647957"
DOMAIN = "liaa.cloud"

def upload_urls():
    out = subprocess.run(
        [os.path.expanduser("~/workspace/skills/hostinger/bin/hostinger-api"),
         "POST", "/api/hosting/v1/files/upload-urls",
         "--data", json.dumps({"username": USERNAME, "domain": DOMAIN})],
        capture_output=True, text=True, check=True)
    return json.loads(out.stdout)

def tus_put(base, auth_key, rest_key, remote, data):
    dest = f"{base}/{remote}?override=true"
    headers = {"Tus-Resumable": "1.0.0", "X-Auth": auth_key,
               "X-Auth-Rest": rest_key, "Upload-Length": str(len(data)),
               "Upload-Offset": "0"}
    req = urllib.request.Request(dest, data=b"", method="POST", headers=headers)
    with urllib.request.urlopen(req, timeout=60) as r:
        if r.status not in (200, 201):
            raise RuntimeError(f"TUS create {remote}: HTTP {r.status}")
    headers2 = {"Tus-Resumable": "1.0.0", "X-Auth": auth_key,
                "X-Auth-Rest": rest_key, "Upload-Offset": "0",
                "Content-Type": "application/offset+octet-stream"}
    req = urllib.request.Request(dest, data=data, method="PATCH", headers=headers2)
    with urllib.request.urlopen(req, timeout=120) as r:
        if r.status not in (200, 204):
            raise RuntimeError(f"TUS patch {remote}: HTTP {r.status}")

def main():
    only = sys.argv[1:] or None
    files = []
    for root, _, names in os.walk(SRC):
        for n in names:
            if n == "subir.py":
                continue  # el script de despliegue no se publica
            full = os.path.join(root, n)
            rel = os.path.relpath(full, SRC).replace(os.sep, "/")
            if only and rel not in only:
                continue
            files.append((rel, full))
    info = upload_urls()
    url, ak, rk = info["url"], info["auth_key"], info["rest_auth_key"]
    for rel, full in sorted(files):
        with open(full, "rb") as f:
            data = f.read()
        remote = f"{PREFIX}/{rel}"
        tus_put(url, ak, rk, remote, data)
        print(f"OK  {remote} ({len(data)} bytes)")
    print(f"Subidos {len(files)} archivos.")

if __name__ == "__main__":
    main()
