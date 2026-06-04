<?php

class Geocoder
{
    public static function buscarCep(string $cep): ?array
    {
        $url = "https://viacep.com.br/ws/{$cep}/json/";

        $response = file_get_contents($url);

        if (!$response) {
            return null;
        }

        $data = json_decode($response, true);

        if (isset($data['erro'])) {
            return null;
        }

        return [
            'cep' => $data['cep'],
            'logradouro' => $data['logradouro'],
            'bairro' => $data['bairro'],
            'cidade' => $data['localidade'],
            'uf' => $data['uf'],
        ];
    }
}