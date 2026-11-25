<?php

namespace iEducar\Packages\BuritiBuscaAtiva\Support;

final class Processos
{
    public const int RAIZ = 882200;
    public const int PAINEL = 882201;
    public const int ALERTAS = 882202;
    public const int PROCESSOS = 882203;
    public const int CONFIGURACAO = 882204;
    public const int API_TOKENS = 882205;
    public const int RELATORIOS = 882206;

    /** @return array<int, string> */
    public static function titulos(): array
    {
        return [
            self::RAIZ => 'Busca Ativa',
            self::PAINEL => 'Painel',
            self::ALERTAS => 'Alertas',
            self::PROCESSOS => 'Processos',
            self::CONFIGURACAO => 'Configuração',
            self::API_TOKENS => 'Tokens API (migrado → Apps)',
            self::RELATORIOS => 'Relatórios',
        ];
    }

    /** @return list<int> */
    public static function todos(): array
    {
        return array_keys(self::titulos());
    }
}
