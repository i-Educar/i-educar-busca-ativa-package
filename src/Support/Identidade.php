<?php

namespace iEducar\Packages\BuritiBuscaAtiva\Support;

final class Identidade
{
    public const COR = '#9a3412';
    public const DESTAQUE = '#c2410c';
    public const COR_CLARA = '#fff7ed';
    public const ICONE = 'fa-search';
    public const NOME = 'Busca Ativa';
    public const DESCRICAO = 'Infrequência, alertas e reintegração';

    /** @return array{cor:string,destaque:string,clara:string,icone:string,nome:string,descricao:string} */
    public static function tokens(): array
    {
        return [
            'cor' => self::COR,
            'destaque' => self::DESTAQUE,
            'clara' => self::COR_CLARA,
            'icone' => self::ICONE,
            'nome' => self::NOME,
            'descricao' => self::DESCRICAO,
        ];
    }
}
