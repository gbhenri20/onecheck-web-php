"""Atualiza dados de exemplo na API (mantém usuários). Uso: SEED_SECRET=xxx py refresh_data_render.py [POST|PUT|PATCH|status]"""
import json
import os
import sys
import urllib.error
import urllib.request

BASE = os.getenv("ONECHECK_API_URL", "https://onecheck-api.onrender.com/api/v1").rstrip("/")
if not BASE.endswith("/api/v1"):
    BASE += "/api/v1"
SECRET = os.getenv("SEED_SECRET", "")


def call(method: str, path: str, body: dict | None = None) -> tuple[int, dict | str]:
    url = BASE + path
    data = json.dumps(body).encode() if body is not None else None
    headers = {"Accept": "application/json", "X-Seed-Secret": SECRET}
    if data is not None:
        headers["Content-Type"] = "application/json"
    req = urllib.request.Request(url, data=data, headers=headers, method=method.upper())
    try:
        with urllib.request.urlopen(req, timeout=120) as resp:
            raw = resp.read().decode()
            return resp.status, json.loads(raw) if raw else {}
    except urllib.error.HTTPError as e:
        raw = e.read().decode()
        try:
            return e.code, json.loads(raw)
        except json.JSONDecodeError:
            return e.code, raw


def main() -> int:
    if not SECRET:
        print("Defina SEED_SECRET (Render → onecheck-api → Environment).")
        return 1

    action = (sys.argv[1] if len(sys.argv) > 1 else "POST").upper()

    if action == "STATUS":
        code, payload = call("GET", "/admin/data/status")
    elif action in ("POST", "PUT", "PATCH"):
        code, payload = call(action, "/admin/refresh-data")
    elif action == "SEED":
        code, payload = call("POST", "/admin/seed", {"mode": "refresh_data"})
    else:
        print("Uso: py refresh_data_render.py [POST|PUT|PATCH|STATUS|SEED]")
        return 1

    print(json.dumps(payload, indent=2, ensure_ascii=False) if isinstance(payload, dict) else payload)
    return 0 if isinstance(payload, dict) and payload.get("sucesso") else (0 if action == "STATUS" and code == 200 else 1)


if __name__ == "__main__":
    sys.exit(main())
