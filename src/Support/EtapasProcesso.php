<?php

namespace iEducar\Packages\BuritiBuscaAtiva\Support;

/**
 * Etapas operacionais do processo de busca ativa (ordem do fluxo).
 */
final class EtapasProcesso
{
    public const CONTATO_INICIAL = 'contato_inicial';

    public const VISITA = 'visita';

    public const ENCAMINHAMENTO = 'encaminhamento';

    public const ACOMPANHAMENTO = 'acompanhamento';

    public const RETORNO = 'retorno';

    /** @return list<string> */
    public static function todas(): array
    {
        return [
            self::CONTATO_INICIAL,
            self::VISITA,
            self::ENCAMINHAMENTO,
            self::ACOMPANHAMENTO,
            self::RETORNO,
        ];
    }

    public static function rotulo(string $etapa): string
    {
        return match ($etapa) {
            self::CONTATO_INICIAL => 'Contato inicial',
            self::VISITA => 'Visita domiciliar',
            self::ENCAMINHAMENTO => 'Encaminhamento (SME / rede)',
            self::ACOMPANHAMENTO => 'Acompanhamento familiar',
            self::RETORNO => 'Retorno às aulas',
            default => $etapa,
        };
    }

    /** @return list<string> */
    public static function resultadosEncerramento(): array
    {
        return ['retorno', 'transferencia', 'abandono', 'outro'];
    }
}
