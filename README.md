# OneCheck

Sistema web em **PHP + MySQL + Bootstrap** para gestão de imóveis, **vistorias com fotos** (recebidas pelo APK **Kotlin**), **contratos** e problemas.

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

- PHP 8.1+ (extensões: pdo_mysql, fileinfo, json)
- MySQL 8+
- Apache (XAMPP/Laragon) ou nginx + php-fpm

## Instalação (XAMPP no Windows)

1. Copie a pasta `onecheck` para `C:\xampp\htdocs\onecheck`
2. No phpMyAdmin ou terminal MySQL, execute:
   - `database/migrations/001_schema.sql`
3. Ajuste `config/database.php` se necessário (usuário/senha do MySQL)
4. Acesse no navegador: `http://localhost/onecheck/public/install.php`
5. Login: `http://localhost/onecheck/public/login.php`
   - E-mail: `admin@onecheck.local`
   - Senha: `admin123`

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
