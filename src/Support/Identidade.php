<?php

namespace iEducar\Packages\BuritiBuscaAtiva\Support;

final class Identidade
{
    public const COR = '#a85f00';
    public const DESTAQUE = '#c98900';
    public const COR_CLARA = '#fff5e6';
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
