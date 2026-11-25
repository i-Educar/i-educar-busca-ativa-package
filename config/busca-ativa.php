<?php

return [
    'public' => [
        'aparecer_na_home' => false,
        'titulo' => 'Busca Ativa',
        'ordem' => 58,
        'url' => null,
    ],

    'enabled' => env('BUSCA_ATIVA_ENABLED', true),

    'limites' => [
        'faltas_consecutivas_alerta' => (int) env('BUSCA_ATIVA_FALTAS_CONSECUTIVAS', 3),
        'faltas_mensais_alerta' => (int) env('BUSCA_ATIVA_FALTAS_MENSAIS', 5),
        'frequencia_minima_percentual' => (float) env('BUSCA_ATIVA_FREQ_MINIMA', 75),
        'dias_sem_contato_escalar' => (int) env('BUSCA_ATIVA_DIAS_SEM_CONTATO', 7),
    ],

    'atendimento' => [
        'gerar_alerta' => true,
        'prioridade_padrao' => 3,
        'tipo_poc' => 'alerta_busca_ativa',
    ],

    'api' => [
        'token_ttl_horas' => (int) env('BUSCA_ATIVA_API_TOKEN_TTL', 72),
        'rate_limit_por_minuto' => 60,
    ],
];
