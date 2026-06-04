-- OneCheck — schema PostgreSQL (base + API mobile + checklist)
-- Execute: psql $DATABASE_URL -f database/migrations/001_schema_postgres.sql

CREATE EXTENSION IF NOT EXISTS "pgcrypto";

-- Usuários
CREATE TABLE IF NOT EXISTS usuarios (
    id              SERIAL PRIMARY KEY,
    uuid            UUID NOT NULL DEFAULT gen_random_uuid() UNIQUE,
    nome            VARCHAR(120) NOT NULL,
    email           VARCHAR(180) NOT NULL UNIQUE,
    senha_hash      VARCHAR(255) NOT NULL,
    perfil          VARCHAR(32) NOT NULL DEFAULT 'admin',
    ativo           SMALLINT NOT NULL DEFAULT 1,
    mfa_secret      VARCHAR(64),
    mfa_enabled     SMALLINT NOT NULL DEFAULT 0,
    mfa_obrigatorio SMALLINT NOT NULL DEFAULT 0,
    criado_em       TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    atualizado_em   TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Imóveis
CREATE TABLE IF NOT EXISTS imoveis (
    id              SERIAL PRIMARY KEY,
    uuid            UUID NOT NULL DEFAULT gen_random_uuid() UNIQUE,
    codigo          VARCHAR(32) NOT NULL UNIQUE,
    titulo          VARCHAR(200) NOT NULL,
    endereco        VARCHAR(255) NOT NULL DEFAULT '',
    cidade          VARCHAR(100) NOT NULL DEFAULT '',
    estado          CHAR(2) NOT NULL DEFAULT '',
    cep             VARCHAR(12),
    tipo            VARCHAR(32) NOT NULL DEFAULT 'apartamento',
    tamanho_m2      NUMERIC(10, 2),
    garagem         VARCHAR(32) NOT NULL DEFAULT 'nenhuma',
    status          VARCHAR(32) NOT NULL DEFAULT 'disponivel',
    observacoes     TEXT,
    criado_em       TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    atualizado_em   TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS enderecos (
    id                UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    imovel_id         INTEGER NOT NULL REFERENCES imoveis(id) ON DELETE CASCADE,
    logradouro        VARCHAR(200) NOT NULL,
    numero            VARCHAR(20),
    complemento       VARCHAR(80),
    bairro            VARCHAR(100),
    cidade            VARCHAR(100) NOT NULL,
    estado            CHAR(2) NOT NULL,
    cep               VARCHAR(12),
    latitude          DOUBLE PRECISION,
    longitude         DOUBLE PRECISION,
    principal         SMALLINT NOT NULL DEFAULT 1,
    geocodificado_em  TIMESTAMPTZ
);

CREATE INDEX IF NOT EXISTS idx_enderecos_imovel ON enderecos(imovel_id);

CREATE TABLE IF NOT EXISTS imovel_comodos (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    imovel_id   INTEGER NOT NULL REFERENCES imoveis(id) ON DELETE CASCADE,
    tipo        VARCHAR(48) NOT NULL,
    descricao   VARCHAR(120),
    ordem       INTEGER NOT NULL DEFAULT 0,
    ativo       SMALLINT NOT NULL DEFAULT 1
);

CREATE INDEX IF NOT EXISTS idx_imovel_comodos_imovel ON imovel_comodos(imovel_id);

-- Contratos
CREATE TABLE IF NOT EXISTS contratos (
    id                      SERIAL PRIMARY KEY,
    imovel_id               INTEGER NOT NULL REFERENCES imoveis(id),
    numero                  VARCHAR(40) NOT NULL,
    locatario_nome          VARCHAR(120) NOT NULL DEFAULT '',
    locatario_documento     VARCHAR(32),
    locatario_usuario_id    INTEGER REFERENCES usuarios(id),
    status                  VARCHAR(24) NOT NULL DEFAULT 'rascunho',
    data_inicio             DATE,
    data_fim                DATE,
    valor_mensal            NUMERIC(12, 2),
    observacoes             TEXT,
    criado_em               TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    atualizado_em           TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS contrato_anexos (
    id              SERIAL PRIMARY KEY,
    contrato_id     INTEGER NOT NULL REFERENCES contratos(id) ON DELETE CASCADE,
    titulo          VARCHAR(120) NOT NULL,
    arquivo_nome    VARCHAR(255) NOT NULL,
    arquivo_path    VARCHAR(500) NOT NULL,
    tipo            VARCHAR(32) NOT NULL DEFAULT 'contrato',
    criado_em       TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Vistorias
CREATE TABLE IF NOT EXISTS vistorias (
    id                    SERIAL PRIMARY KEY,
    imovel_id             INTEGER NOT NULL REFERENCES imoveis(id),
    usuario_id            INTEGER NOT NULL REFERENCES usuarios(id),
    tipo                  VARCHAR(24) NOT NULL DEFAULT 'entrada',
    status                VARCHAR(24) NOT NULL DEFAULT 'rascunho',
    data_vistoria         DATE NOT NULL DEFAULT CURRENT_DATE,
    observacoes           TEXT,
    sincronizado_mobile   SMALLINT NOT NULL DEFAULT 0,
    criado_em             TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    atualizado_em         TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS vistoria_fotos (
    id              SERIAL PRIMARY KEY,
    vistoria_id     INTEGER NOT NULL REFERENCES vistorias(id) ON DELETE CASCADE,
    comodo          VARCHAR(64) NOT NULL,
    arquivo_nome    VARCHAR(255) NOT NULL,
    arquivo_path    VARCHAR(500) NOT NULL,
    mime_type       VARCHAR(80),
    tamanho_bytes   INTEGER,
    latitude        DOUBLE PRECISION,
    longitude       DOUBLE PRECISION,
    origem          VARCHAR(16) NOT NULL DEFAULT 'web',
    observacao      TEXT,
    criado_em       TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS vistoria_checklist (
    vistoria_id   INTEGER NOT NULL REFERENCES vistorias(id) ON DELETE CASCADE,
    comodo        VARCHAR(64) NOT NULL,
    situacao      VARCHAR(16) NOT NULL DEFAULT 'ok',
    observacao    TEXT,
    PRIMARY KEY (vistoria_id, comodo)
);

-- Problemas
CREATE TABLE IF NOT EXISTS problemas (
    id              SERIAL PRIMARY KEY,
    imovel_id       INTEGER NOT NULL REFERENCES imoveis(id),
    vistoria_id     INTEGER REFERENCES vistorias(id) ON DELETE SET NULL,
    titulo          VARCHAR(200) NOT NULL,
    descricao       TEXT,
    prioridade      VARCHAR(16) NOT NULL DEFAULT 'media',
    status          VARCHAR(24) NOT NULL DEFAULT 'aberto',
    criado_por      INTEGER NOT NULL REFERENCES usuarios(id),
    criado_em       TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    resolvido_em    TIMESTAMPTZ
);

-- Auth API / JWT
CREATE TABLE IF NOT EXISTS api_tokens (
    id              SERIAL PRIMARY KEY,
    usuario_id      INTEGER NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
    token_hash      CHAR(64) NOT NULL UNIQUE,
    dispositivo     VARCHAR(120),
    expira_em       TIMESTAMPTZ,
    revogado        SMALLINT NOT NULL DEFAULT 0,
    ultimo_uso      TIMESTAMPTZ,
    criado_em       TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS auth_refresh_tokens (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    usuario_id      INTEGER NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
    token_hash      CHAR(64) NOT NULL UNIQUE,
    expira_em       TIMESTAMPTZ NOT NULL,
    revogado        SMALLINT NOT NULL DEFAULT 0,
    ip              VARCHAR(45),
    user_agent      VARCHAR(500),
    criado_em       TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS log_operacao (
    id                UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    usuario_id        INTEGER REFERENCES usuarios(id) ON DELETE SET NULL,
    acao              VARCHAR(48) NOT NULL,
    entidade          VARCHAR(48) NOT NULL,
    entidade_id       VARCHAR(64),
    payload_anterior  JSONB,
    payload_novo      JSONB,
    ip                VARCHAR(45),
    user_agent        VARCHAR(500),
    criado_em         TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_vistorias_imovel ON vistorias(imovel_id);
CREATE INDEX IF NOT EXISTS idx_problemas_imovel ON problemas(imovel_id);
CREATE INDEX IF NOT EXISTS idx_contratos_locatario ON contratos(locatario_usuario_id);
