<?php

namespace iEducar\Packages\BuritiBuscaAtiva\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class ConfiguracaoService
{
    /** @return array<string, mixed> */
    public static function obter(?int $instituicao = null): array
    {
        $defaults = [
            'faltas_consecutivas_alerta' => (int) config('busca-ativa.limites.faltas_consecutivas_alerta', 3),
            'faltas_mensais_alerta' => (int) config('busca-ativa.limites.faltas_mensais_alerta', 5),
            'frequencia_minima_percentual' => (float) config('busca-ativa.limites.frequencia_minima_percentual', 75),
            'dias_sem_contato_escalar' => (int) config('busca-ativa.limites.dias_sem_contato_escalar', 7),
            'gerar_alerta_atendimento' => (bool) config('busca-ativa.atendimento.gerar_alerta', true),
            'notificar_familia' => true,
            'somente_cursos_marcados' => (bool) config('busca-ativa.diretivas.somente_cursos_marcados', true),
            'abrir_processo_automatico' => true,
            'regras_json' => [],
            'ativo' => class_exists(\iEducar\Packages\BuritiLagunaAmbiente\Support\ModuloConfiguracao::class)
                ? \iEducar\Packages\BuritiLagunaAmbiente\Support\ModuloConfiguracao::ativo('busca-ativa')
                : true,
            'ref_cod_instituicao' => $instituicao,
        ];

        if (! Schema::hasTable('busca_ativa.configuracao')) {
            return $defaults;
        }

        $instituicao ??= self::instituicaoPadrao();
        $row = DB::table('busca_ativa.configuracao')
            ->where('ref_cod_instituicao', $instituicao)
            ->first();

        if ($row === null) {
            $defaults['ref_cod_instituicao'] = $instituicao;

            return $defaults;
        }

        return [
            'id' => (int) $row->id,
            'ref_cod_instituicao' => (int) $row->ref_cod_instituicao,
            'faltas_consecutivas_alerta' => (int) $row->faltas_consecutivas_alerta,
            'faltas_mensais_alerta' => (int) $row->faltas_mensais_alerta,
            'frequencia_minima_percentual' => (float) $row->frequencia_minima_percentual,
            'dias_sem_contato_escalar' => (int) $row->dias_sem_contato_escalar,
            'gerar_alerta_atendimento' => (bool) $row->gerar_alerta_atendimento,
            'notificar_familia' => (bool) $row->notificar_familia,
            'somente_cursos_marcados' => (bool) $row->somente_cursos_marcados,
            'abrir_processo_automatico' => (bool) $row->abrir_processo_automatico,
            'regras_json' => is_string($row->regras_json)
                ? (json_decode($row->regras_json, true) ?: [])
                : (array) $row->regras_json,
            'ativo' => (bool) $row->ativo
                && (! class_exists(\iEducar\Packages\BuritiLagunaAmbiente\Support\ModuloConfiguracao::class)
                    || \iEducar\Packages\BuritiLagunaAmbiente\Support\ModuloConfiguracao::ativo('busca-ativa')),
        ];
    }

    /** @param array<string, mixed> $dados */
    public static function salvar(array $dados, ?int $instituicao = null): void
    {
        if (! Schema::hasTable('busca_ativa.configuracao')) {
            return;
        }

        $instituicao ??= self::instituicaoPadrao();
        $now = now();
        DB::table('busca_ativa.configuracao')->updateOrInsert(
            ['ref_cod_instituicao' => $instituicao],
            [
                'faltas_consecutivas_alerta' => (int) ($dados['faltas_consecutivas_alerta'] ?? 3),
                'faltas_mensais_alerta' => (int) ($dados['faltas_mensais_alerta'] ?? 5),
                'frequencia_minima_percentual' => (float) ($dados['frequencia_minima_percentual'] ?? 75),
                'dias_sem_contato_escalar' => (int) ($dados['dias_sem_contato_escalar'] ?? 7),
                'gerar_alerta_atendimento' => (bool) ($dados['gerar_alerta_atendimento'] ?? true),
                'notificar_familia' => (bool) ($dados['notificar_familia'] ?? true),
                'somente_cursos_marcados' => (bool) ($dados['somente_cursos_marcados'] ?? true),
                'abrir_processo_automatico' => (bool) ($dados['abrir_processo_automatico'] ?? true),
                'regras_json' => json_encode($dados['regras_json'] ?? [], JSON_UNESCAPED_UNICODE),
                'ativo' => (bool) ($dados['ativo'] ?? true),
                'updated_at' => $now,
                'created_at' => $now,
            ],
        );
    }

    public static function instituicaoPadrao(): int
    {
        return (int) (DB::table('pmieducar.instituicao')->where('ativo', 1)->value('cod_instituicao') ?? 1);
    }
}
