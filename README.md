# OneCheck

Sistema web em **PHP + PostgreSQL + Bootstrap** para gestão de imóveis, **vistorias com fotos** (recebidas pelo APK **Kotlin**), **contratos** e problemas.

## Estrutura de pastas

```
onecheck/
├── api/              # REST JSON para o app mobile
│   ├── auth/
│   ├── imoveis/
│   ├── vistorias/    # create, upload, list
│   ├── contratos/
│   └── problemas/
├── assets/           # CSS, JS, imagens, uploads
├── config/           # database, session, permissions
├── contratos/        # telas web de contratos
├── dashboard/
├── database/         # migrations e backups
├── imoveis/
├── includes/         # header, navbar, auth, PDO
├── mobile/           # documentação Kotlin
├── problemas/
├── public/           # login (ponto de entrada web)
├── usuarios/
└── vistorias/        # fotos arquivadas no painel
```

## Requisitos

- PHP 8.1+ (extensões: `pdo_pgsql`, `fileinfo`, `json`; opcional `pdo_mysql` para legado)
- PostgreSQL 14+ (recomendado) ou MySQL 8+ com `ONECHECK_DB_DRIVER=mysql`
- Apache (XAMPP/Laragon) ou nginx + php-fpm / Docker

## Instalação local (PostgreSQL)

1. Crie o banco `onecheck` e importe o schema:
   ```bash
   psql -U postgres -d onecheck -f database/migrations/001_schema_postgres.sql
   ```
2. Variáveis opcionais: `ONECHECK_DB_HOST`, `ONECHECK_DB_USER`, `ONECHECK_DB_PASS`, `ONECHECK_DB_NAME`
3. Acesse: `http://localhost/onecheck/public/install.php` (ajuste `base_path` em `config/app.php` se necessário)
4. Login: `public/login.php` — `admin@onecheck.local` / `admin123`

## Instalação (XAMPP + MySQL legado)

1. Defina `ONECHECK_DB_DRIVER=mysql` e importe o SQL MySQL (se disponível no projeto).
2. Copie a pasta para `htdocs/onecheck` e configure `config/database.php`.

## Deploy no Render

Use o Blueprint `render.yaml`: cria o Postgres (`onecheck-db`), injeta `DATABASE_URL` no PHP e aplica o schema automaticamente ao iniciar o container. Depois do deploy, acesse apenas `public/install.php` para criar o admin.

## API mobile

Documentação completa: [mobile/API_KOTLIN.md](mobile/API_KOTLIN.md)

| Endpoint | Uso |
|----------|-----|
| `POST api/auth/login.php` | Token para o APK |
| `GET api/imoveis/list.php` | Lista imóveis |
| `POST api/vistorias/create.php` | Inicia vistoria |
| `POST api/vistorias/upload.php` | Envia foto do cômodo |
| `GET api/vistorias/list.php` | Lista vistorias |

Fotos são salvas em `assets/uploads/vistorias/{id}/` e exibidas em **Vistorias → Fotos**.

## Próximos passos sugeridos

- [ ] Formulários de contratos (`contratos/novo.php`, anexos PDF)
- [ ] API `api/contratos/` para consulta no mobile
- [ ] Checklist por cômodo em `vistorias/checklist.php`
- [ ] HTTPS e `config/session.php` → `secure: true` em produção
- [ ] Remover `public/install.php` após deploy

## Segurança

- Troque a senha do admin após o primeiro acesso
- Em produção, restrinja CORS da API ao domínio do app
- Valide tamanho máximo de upload no `php.ini` (`upload_max_filesize`)
