<?php
/**
 * OneCheck — Configuração da API
 * Toda comunicação com o backend passa por este arquivo.
 */

define('API_BASE_URL', 'http://3.145.6.22:8000/api/v1');
define('API_TIMEOUT',  10);

// ============================================================
// Cliente HTTP central
// ============================================================
class ApiClient
{
    private static function token(): ?string
    {
        return $_SESSION['api_token'] ?? null;
    }

    public static function get(string $path, array $query = []): array
    {
        $url = API_BASE_URL . $path;
        if ($query) $url .= '?' . http_build_query($query);
        return self::request('GET', $url);
    }

    public static function post(string $path, array $body = []): array
    {
        return self::request('POST', API_BASE_URL . $path, $body);
    }

    public static function put(string $path, array $body = []): array
    {
        return self::request('PUT', API_BASE_URL . $path, $body);
    }

    public static function patch(string $path, array $body = []): array
    {
        return self::request('PATCH', API_BASE_URL . $path, $body);
    }

    public static function delete(string $path): array
    {
        return self::request('DELETE', API_BASE_URL . $path);
    }

    /**
     * Executa múltiplas requisições GET em paralelo para melhorar a performance.
     * @param array $requests Associative array ['key' => '/path?query']
     */
    public static function multi_get(array $requests): array
    {
        $mh = curl_multi_init();
        $handles = [];
        $results = [];

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];
        $token = self::token();
        if ($token) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        foreach ($requests as $key => $path) {
            $url = API_BASE_URL . $path;
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => API_TIMEOUT,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            curl_multi_add_handle($mh, $ch);
            $handles[$key] = $ch;
        }

        $active = null;
        do {
            $mrc = curl_multi_exec($mh, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);

        while ($active && $mrc == CURLM_OK) {
            if (curl_multi_select($mh) != -1) {
                do {
                    $mrc = curl_multi_exec($mh, $active);
                } while ($mrc == CURLM_CALL_MULTI_PERFORM);
            }
        }

        foreach ($handles as $key => $ch) {
            $raw = curl_multi_getcontent($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $results[$key] = json_decode($raw ?: '', true) ?? ['sucesso' => false, 'erro' => 'Resposta inválida'];
            $results[$key]['_status'] = $code;
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }

        curl_multi_close($mh);
        return $results;
    }

    private static function request(string $method, string $url, ?array $body = null, bool $attemptRefresh = true): array
    {
        $response = self::doRequest($method, $url, $body, true);

        if ($response['_status'] === 401 && $attemptRefresh && self::tryRefreshToken()) {
            $response = self::doRequest($method, $url, $body, true);
        }

        return $response;
    }

    private static function doRequest(string $method, string $url, ?array $body = null, bool $withAuth = true): array
    {
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        if ($withAuth) {
            $token = self::token();
            if ($token) {
                $headers[] = 'Authorization: Bearer ' . $token;
            }
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => API_TIMEOUT,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $raw  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err || !$raw) {
            return ['sucesso' => false, 'erro' => 'API indisponível: ' . $err, '_status' => 0];
        }

        $data = json_decode($raw, true) ?? ['sucesso' => false, 'erro' => 'Resposta inválida'];
        $data['_status'] = $code;
        return $data;
    }

    private static function tryRefreshToken(): bool
    {
        $refreshToken = $_SESSION['api_refresh_token'] ?? null;
        if (!$refreshToken) {
            return false;
        }

        $refreshResponse = self::doRequest('POST', API_BASE_URL . '/auth/refresh', ['refresh_token' => $refreshToken], false);
        if (!empty($refreshResponse['sucesso']) && !empty($refreshResponse['dados']['access_token'])) {
            $_SESSION['api_token'] = $refreshResponse['dados']['access_token'];
            if (!empty($refreshResponse['dados']['refresh_token'])) {
                $_SESSION['api_refresh_token'] = $refreshResponse['dados']['refresh_token'];
            }
            return true;
        }

        return false;
    }
}
