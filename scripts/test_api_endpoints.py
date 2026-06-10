"""Test aceitar vistoria and criar problema on Render API."""
import json
import urllib.error
import urllib.request

BASE = "https://onecheck-api.onrender.com/api/v1"


def req(method, path, body=None, token=None):
    url = BASE + path
    data = json.dumps(body).encode() if body is not None else None
    headers = {"Content-Type": "application/json", "Accept": "application/json"}
    if token:
        headers["Authorization"] = f"Bearer {token}"
    r = urllib.request.Request(url, data=data, headers=headers, method=method)
    try:
        with urllib.request.urlopen(r, timeout=90) as resp:
            raw = resp.read().decode()
            return resp.status, json.loads(raw) if raw else {}
    except urllib.error.HTTPError as e:
        raw = e.read().decode()
        try:
            payload = json.loads(raw) if raw else {"detail": str(e)}
        except json.JSONDecodeError:
            payload = {"raw": raw}
        return e.code, payload


def main():
    code, login = req("POST", "/auth/login", {"email": "locatario@onecheck.local", "senha": "locat123"})
    print("login", code, login)
    if not login.get("sucesso"):
        return
    token = login["dados"]["access_token"]

    code, contratos = req("GET", "/contratos?por_pagina=5&status=ativo", token=token)
    print("contratos", code, len(contratos.get("dados", [])))
    contratos_list = contratos.get("dados") or []
    if not contratos_list:
        print("no contratos")
        return
    cid = contratos_list[0]["id"]

    code, prob = req(
        "POST",
        f"/contratos/{cid}/problemas",
        {"titulo": "Teste API", "descricao": "teste", "prioridade": "normal", "status": "aberto"},
        token=token,
    )
    print("post problema", code, prob)

    code, cks = req("GET", f"/contratos/{cid}/checklists", token=token)
    print("checklists", code, cks)
    checklists = cks.get("dados") or []
    pendente = next((c for c in checklists if c.get("status") == "pendente_aceite"), None)
    if not pendente and checklists:
        pendente = checklists[0]
    if pendente:
        ck_id = pendente["id"]
        code, aceitar = req("PATCH", f"/checklists/{ck_id}/aceitar", {}, token=token)
        print("patch aceitar", code, aceitar)
    else:
        print("no checklist to test aceitar")


if __name__ == "__main__":
    main()
