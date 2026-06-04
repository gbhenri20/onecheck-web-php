<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/api/bootstrap.php';

/*api_require_method('GET');
if (!Auth::check()) {
    api_error('Não autenticado', 401);
}*/

$cep = preg_replace('/\D/', '', $_GET['cep'] ?? '') ?? '';
if (strlen($cep) !== 8) {
    api_error('CEP inválido', 422);
}

$dados = Geocoder::buscarCep($cep);
if (!$dados) {
    api_error('CEP não encontrado', 404);
}

api_ok(['endereco' => $dados]);
