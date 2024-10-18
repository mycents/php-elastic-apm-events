<?php

include 'configuration.class.php';

$config = new configuration();

// URL do APM Server
$apmServerUrl = "{$config->server_url}/intake/v2/events";

// Token secreto para autenticação (se necessário)
$secretToken = $config->secret_token; //SEU_TOKEN_SECRETO

// Dados do evento
$eventData = [];

// Metadados (necessário enviar uma vez por sessão)
//metadata_process_title=''
$eventData[] = json_encode([
    'metadata' => [
        'service' => [
            'name' => $config->service_name,
            'version' => $config->version,
            'environment' => $config->environment,
            'agent' => [
                'name' => 'elastic-apm-agent',
                'version' => '5.4.45'
            ],
            'language' => [
                'name' => 'PHP',
                'version' => PHP_VERSION
            ],
            'runtime' => [
                'name' => 'PHP',
                'version' => PHP_VERSION
            ]
        ],
        'process' => [
            'pid' => getmypid(),
            'title' => 'Meu Processo PHP'
        ],
        'system' => [
            'hostname' => gethostname(),
            'architecture' => php_uname('m'),
            'platform' => php_uname('s')
        ]
    ]
]);

// Criando uma transação de exemplo
$traceId = bin2hex(random_bytes(16)); //rastreamento

$transactionId = bin2hex(random_bytes(8));
$eventData[] = json_encode([
    'transaction' => [
        'id' => $transactionId,
        'trace_id' => $traceId,
        'name' => 'Transacao de Teste',
        'type' => 'request',
        'timestamp' => round(microtime(true) * 1000000), // em microssegundos
        'duration' => 200, // duração em milissegundos
        'result' => '200',
        'sampled' => true,
        'span_count' => [
            'started' => 0, // Número de spans iniciados
            'dropped' => 0  // Número de spans descartados
        ],
        'context' => [
            'request' => [
                'method' => 'GET',
                'url' => [
                    'full' => 'http://exemplo.com/teste',
                    'protocol' => 'http',
                    'hostname' => 'exemplo.com',
                    'port' => 80,
                    'pathname' => '/teste',
                    'search' => ''
                ]
            ],
            'response' => [
                'status_code' => 200
            ]
        ]
    ]
]);

$parentId = $transactionId;
$spanId = bin2hex(random_bytes(8));
$eventData[] = json_encode([
    'span' => [
        'id' => $spanId,
        'transaction_id' => $transactionId,
        'parent_id' => $parentId, // O span é filho da transação
        'trace_id' => $traceId,
        'name' => 'Consulta ao Banco de Dados (1)',
        'type' => 'db.mysql.query',
        'start' => 50, // Início relativo à transação em milissegundos
        'duration' => 100, // Duração do span em milissegundos
        'context' => [
            'db' => [
                'instance' => 'meu_banco_de_dados',
                'statement' => 'SELECT * FROM usuarios WHERE id = 1',
                'type' => 'sql',
                'user' => 'usuario_db'
            ]
        ]
    ]
]);

$parentId = $spanId;
$spanId = bin2hex(random_bytes(8));
$eventData[] = json_encode([
    'span' => [
        'id' => $spanId,
        'transaction_id' => $transactionId,
        'parent_id' => $parentId, // O span é filho da transação
        'trace_id' => $traceId,
        'name' => 'Consulta ao Banco de Dados (2)',
        'type' => 'db.mysql.query',
        'start' => 50, // Início relativo à transação em milissegundos
        'duration' => 100, // Duração do span em milissegundos
        'context' => [
            'db' => [
                'instance' => 'meu_banco_de_dados',
                'statement' => 'SELECT * FROM usuarios WHERE id = 2',
                'type' => 'sql',
                'user' => 'usuario_db'
            ]
        ]
    ]
]);

$parentId = $spanId;
$spanId = bin2hex(random_bytes(8));
$eventData[] = json_encode([
    'span' => [
        'id' => $spanId,
        'transaction_id' => $transactionId,
        'parent_id' => $parentId, // O span é filho da transação
        'trace_id' => $traceId,
        'name' => 'Consulta ao Banco de Dados (3)',
        'type' => 'db.mysql.query',
        'start' => 50, // Início relativo à transação em milissegundos
        'duration' => 100, // Duração do span em milissegundos
        'context' => [
            'db' => [
                'instance' => 'meu_banco_de_dados',
                'statement' => 'SELECT * FROM usuarios WHERE id = 3',
                'type' => 'sql',
                'user' => 'usuario_db'
            ]
        ]
    ]
]);




// Convertendo os dados do evento para o formato NDJSON
$payload = implode("\n", $eventData) . "\n";

// Inicializando o cURL
$ch = curl_init($apmServerUrl);

// Configurando as opções do cURL
$headers = [
    'Content-Type: application/x-ndjson',
    'User-Agent: custom-php-agent/1.0.0'
];

// Adicionando o token de autenticação, se necessário
if (!empty($secretToken)) {
    $headers[] = 'Authorization: Bearer ' . $secretToken;
}

curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Enviando a requisição
$response = curl_exec($ch);

// Verificando por erros
if ($response === false) {
    echo 'Erro ao enviar o evento: ' . curl_error($ch);
} else {
    // Verificando o código HTTP de resposta
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($httpCode >= 200 && $httpCode < 300) {
        echo 'Evento enviado com sucesso!';
    } else {
        //echo 'Erro na resposta do servidor APM. Código HTTP: ' . $httpCode . '. Resposta: ' . 
        echo $response;
    }
}

// Fechando a conexão cURL
curl_close($ch);

?>
