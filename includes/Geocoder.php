<?php

declare(strict_types=1);

/**
 * RNF05 — ViaCEP + Nominatim (OpenStreetMap).
 */
final class Geocoder
{
    public static function buscarCep(string $cep): ?array
    {
        $cep = preg_replace('/\D/', '', $cep) ?? '';
        if (strlen($cep) !== 8) {
            return null;
        }

        $url = 'https://viacep.com.br/ws/' . $cep . '/json/';
        $json = self::httpGet($url);
        if (!$json) {
            return null;
        }

        $data = json_decode($json, true);
        if (!is_array($data) || !empty($data['erro'])) {
            return null;
        }

        return [
            'cep'        => $data['cep'] ?? $cep,
            'logradouro' => $data['logradouro'] ?? '',
            'bairro'     => $data['bairro'] ?? '',
            'cidade'     => $data['localidade'] ?? '',
            'estado'     => $data['uf'] ?? '',
            'complemento'=> $data['complemento'] ?? '',
        ];
    }

    public static function geocodeEndereco(
        string $logradouro,
        string $numero,
        string $bairro,
        string $cidade,
        string $estado,
        string $cep = ''
    ): ?array {
        $partes = array_filter([
            trim($logradouro . ($numero !== '' ? ', ' . $numero : '')),
            $bairro,
            $cidade,
            $estado,
            'Brasil',
            $cep !== '' ? 'CEP ' . $cep : '',
        ]);
        $q = rawurlencode(implode(', ', $partes));

        $url = 'https://nominatim.openstreetmap.org/search?q=' . $q
            . '&format=json&limit=1&countrycodes=br';

        $json = self::httpGet($url, [
            'User-Agent: OneCheck/1.0 (imoveis-geocoder)',
        ]);
        if (!$json) {
            return null;
        }

        $list = json_decode($json, true);
        if (!is_array($list) || $list === []) {
            return null;
        }

        return [
            'latitude'  => (float) $list[0]['lat'],
            'longitude' => (float) $list[0]['lon'],
        ];
    }

    private static function httpGet(string $url, array $headers = []): ?string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 8,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            $body = curl_exec($ch);
            curl_close($ch);
            return is_string($body) ? $body : null;
        }

        $ctx = stream_context_create([
            'http' => [
                'timeout' => 8,
                'header'  => implode("\r\n", $headers),
            ],
        ]);
        $body = @file_get_contents($url, false, $ctx);
        return is_string($body) ? $body : null;
    }
}
