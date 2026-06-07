<?php

declare(strict_types=1);

final class ImovelService
{
    public static function config(): array
    {
        return require ONECHECK_ROOT . '/config/imoveis.php';
    }

    public static function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    public static function getEnderecoPrincipal(int $imovelId): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM enderecos WHERE imovel_id = ? AND principal = 1 ORDER BY id LIMIT 1'
        );
        $stmt->execute([$imovelId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function enderecoFormatado(?array $end): string
    {
        if (!$end) {
            return '';
        }
        $linha = trim($end['logradouro'] ?? '');
        if (!empty($end['numero'])) {
            $linha .= ', ' . $end['numero'];
        }
        if (!empty($end['bairro'])) {
            $linha .= ' — ' . $end['bairro'];
        }
        return $linha;
    }

    public static function syncImovelLegado(int $imovelId, array $end): void
    {
        $logradouro = self::enderecoFormatado($end);
        Database::pdo()->prepare(
            'UPDATE imoveis SET endereco = ?, cidade = ?, estado = ?, cep = ? WHERE id = ?'
        )->execute([
            $logradouro ?: ($end['logradouro'] ?? ''),
            $end['cidade'] ?? '',
            $end['estado'] ?? '',
            $end['cep'] ?? null,
            $imovelId,
        ]);
    }

    public static function salvarEndereco(int $imovelId, array $data, bool $geocodificar = true): void
    {
        $pdo = Database::pdo();
        $existente = self::getEnderecoPrincipal($imovelId);

        $lat = $data['latitude'] ?? null;
        $lng = $data['longitude'] ?? null;

        if ($geocodificar && ($lat === null || $lng === null)) {
            $geo = Geocoder::geocodeEndereco(
                $data['logradouro'] ?? '',
                $data['numero'] ?? '',
                $data['bairro'] ?? '',
                $data['cidade'] ?? '',
                $data['estado'] ?? '',
                $data['cep'] ?? ''
            );
            if ($geo) {
                $lat = $geo['latitude'];
                $lng = $geo['longitude'];
            }
        }

        $geoEm = ($lat !== null && $lng !== null) ? date('Y-m-d H:i:s') : null;

        if ($existente) {
            $pdo->prepare(
                'UPDATE enderecos SET logradouro=?, numero=?, complemento=?, bairro=?, cidade=?, estado=?, cep=?,
                 latitude=?, longitude=?, geocodificado_em=? WHERE id=?'
            )->execute([
                $data['logradouro'], $data['numero'] ?: null, $data['complemento'] ?: null,
                $data['bairro'] ?: null, $data['cidade'], $data['estado'], $data['cep'] ?: null,
                $lat, $lng, $geoEm, $existente['id'],
            ]);
            $endRow = array_merge($existente, $data, ['latitude' => $lat, 'longitude' => $lng]);
        } else {
            $id = self::uuid();
            $pdo->prepare(
                'INSERT INTO enderecos (id, imovel_id, logradouro, numero, complemento, bairro, cidade, estado, cep,
                 latitude, longitude, principal, geocodificado_em)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)'
            )->execute([
                $id, $imovelId, $data['logradouro'], $data['numero'] ?: null, $data['complemento'] ?: null,
                $data['bairro'] ?: null, $data['cidade'], $data['estado'], $data['cep'] ?: null,
                $lat, $lng, $geoEm,
            ]);
            $endRow = $data;
            $endRow['latitude'] = $lat;
            $endRow['longitude'] = $lng;
        }

        self::syncImovelLegado($imovelId, $endRow);
    }

    /** @return array<string, mixed> */
    public static function parsePostImovel(array $post): array
    {
        $s = static fn(string $k): string => trim((string) ($post[$k] ?? ''));

        return [
            'codigo'      => $s('codigo'),
            'titulo'      => $s('titulo'),
            'tipo'        => $post['tipo'] ?? 'apartamento',
            'status'      => $post['status'] ?? 'disponivel',
            'tamanho_m2'  => ($post['tamanho_m2'] ?? '') !== ''
                ? (float) str_replace(',', '.', (string) $post['tamanho_m2']) : null,
            'garagem'     => $post['garagem'] ?? 'nenhuma',
            'observacoes' => $s('observacoes') ?: null,
            'endereco'    => [
                'logradouro'  => $s('logradouro'),
                'numero'      => $s('numero'),
                'complemento' => $s('complemento'),
                'bairro'      => $s('bairro'),
                'cidade'      => $s('cidade'),
                'estado'      => strtoupper($s('estado')),
                'cep'         => preg_replace('/\D/', '', $s('cep')) ?: '',
                'latitude'    => ($post['latitude'] ?? '') !== '' ? (float) $post['latitude'] : null,
                'longitude'   => ($post['longitude'] ?? '') !== '' ? (float) $post['longitude'] : null,
            ],
        ];
    }

    public static function validar(array $d, bool $novo = true): ?string
    {
        if ($novo && $d['codigo'] === '') {
            return 'Informe o código do imóvel.';
        }
        if ($d['titulo'] === '') {
            return 'Informe o título.';
        }
        $e = $d['endereco'];
        if ($e['logradouro'] === '' || $e['cidade'] === '' || strlen($e['estado']) !== 2) {
            return 'Preencha logradouro, cidade e UF.';
        }
        return null;
    }

    public static function criar(array $d, bool $geocode = true): int
    {
        $pdo = Database::pdo();
        $pdo->prepare(
            'INSERT INTO imoveis (uuid, codigo, titulo, endereco, cidade, estado, cep, tipo, tamanho_m2, garagem, status, observacoes)
             VALUES (UUID(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $d['codigo'],
            $d['titulo'],
            self::enderecoFormatado($d['endereco']),
            $d['endereco']['cidade'],
            $d['endereco']['estado'],
            $d['endereco']['cep'] ?: null,
            $d['tipo'],
            $d['tamanho_m2'],
            $d['garagem'],
            $d['status'] ?? 'disponivel',
            $d['observacoes'],
        ]);
        $id = (int) $pdo->lastInsertId();
        self::salvarEndereco($id, $d['endereco'], $geocode);
        self::seedComodosPadrao($id);
        AuditLog::record('create', 'imoveis', (string) $id);
        return $id;
    }

    public static function atualizar(int $id, array $d, bool $geocode = true): void
    {
        Database::pdo()->prepare(
            'UPDATE imoveis SET titulo=?, tipo=?, tamanho_m2=?, garagem=?, status=?, observacoes=? WHERE id=?'
        )->execute([
            $d['titulo'], $d['tipo'], $d['tamanho_m2'], $d['garagem'], $d['status'], $d['observacoes'], $id,
        ]);
        self::salvarEndereco($id, $d['endereco'], $geocode);
        AuditLog::record('update', 'imoveis', (string) $id);
    }

    public static function seedComodosPadrao(int $imovelId): void
    {
        $padrao = ['sala', 'cozinha', 'quarto', 'banheiro'];
        $pdo = Database::pdo();
        $ordem = 0;
        foreach ($padrao as $tipo) {
            $chk = $pdo->prepare('SELECT id FROM imovel_comodos WHERE imovel_id = ? AND tipo = ? AND descricao IS NULL');
            $chk->execute([$imovelId, $tipo]);
            if ($chk->fetch()) {
                continue;
            }
            $pdo->prepare(
                'INSERT INTO imovel_comodos (id, imovel_id, tipo, descricao, ordem) VALUES (?, ?, ?, NULL, ?)'
            )->execute([self::uuid(), $imovelId, $tipo, $ordem++]);
        }
    }

    public static function listarComodos(int $imovelId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM imovel_comodos WHERE imovel_id = ? AND ativo = 1 ORDER BY ordem, tipo'
        );
        $stmt->execute([$imovelId]);
        return $stmt->fetchAll();
    }

    public static function adicionarComodo(int $imovelId, string $tipo, string $descricao = ''): void
    {
        $max = Database::pdo()->prepare('SELECT COALESCE(MAX(ordem), 0) + 1 FROM imovel_comodos WHERE imovel_id = ?');
        $max->execute([$imovelId]);
        $ordem = (int) $max->fetchColumn();

        Database::pdo()->prepare(
            'INSERT INTO imovel_comodos (id, imovel_id, tipo, descricao, ordem) VALUES (?, ?, ?, ?, ?)'
        )->execute([
            self::uuid(), $imovelId, $tipo, $descricao !== '' ? $descricao : null, $ordem,
        ]);
    }

    public static function removerComodo(string $comodoId, int $imovelId): void
    {
        Database::pdo()->prepare(
            'UPDATE imovel_comodos SET ativo = 0 WHERE id = ? AND imovel_id = ?'
        )->execute([$comodoId, $imovelId]);
    }

    /** Imóveis com coordenadas para o mapa (RF06) */
    public static function listarParaMapa(): array
    {
        return Database::pdo()->query(
            'SELECT i.id, i.codigo, i.titulo, i.status, i.tipo, e.latitude, e.longitude,
                    e.logradouro, e.numero, e.cidade, e.estado
             FROM imoveis i
             INNER JOIN enderecos e ON e.imovel_id = i.id AND e.principal = 1
             WHERE e.latitude IS NOT NULL AND e.longitude IS NOT NULL
             ORDER BY i.codigo'
        )->fetchAll();
    }
}
