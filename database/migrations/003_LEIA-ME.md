# Migração 003 — Checklist completo

## Ordem de execução

1. `001_schema.sql` — base (se banco novo)
2. `002_checklist.sql` — opcional (legado; pode pular se for usar só o 003)
3. **`003_schema_checklist_completo.sql`** — módulo completo

## Comando (XAMPP, porta 3307)

```powershell
cd C:\xampp\htdocs\onecheck
C:\xampp\mysql\bin\mysql.exe -h 127.0.0.1 -P 3307 -u root -p onecheck < database\migrations\003_schema_checklist_completo.sql
```

## O que esta migração cria

| Tabela | Requisitos |
|--------|------------|
| `enderecos` | RF04, RNF04, RNF05 |
| `imovel_comodos` | RF05 |
| `checklist_item_templates` | RF18 (itens padrão) |
| `agendamentos_vistoria` | RF15 |
| `checklists` + comodos + itens + fotos | RF11–RF20 |
| `checklist_aceites` | RF22–RF23 |
| `problema_fotos`, `problema_atualizacoes` | RF25–RF27 |
| `notificacoes_email` | RF28 |
| `log_operacao` | RNF03 |
| `auth_refresh_tokens` | RNF01 |
| `api_keys` | RNF06 |

Também altera: `usuarios` (locatário, MFA, UUID), `imoveis` (tamanho, garagem, status), `contratos` (locatário usuário, triggers RF09/RF10).

## Erros comuns

- **Triggers:** se o import falhar nos `DELIMITER`, execute os blocos de trigger manualmente no phpMyAdmin.
- **Coluna já existe:** ignore ou remova `IF NOT EXISTS` se MySQL antigo.
- **Status ocupado:** migração renomeia para `locado` automaticamente.

## Próximo passo

Leia `docs/ROADMAP_IMPLEMENTACAO.md` e comece pelo **Sprint 1**.
