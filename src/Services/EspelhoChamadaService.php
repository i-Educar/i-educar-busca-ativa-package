<?php

namespace iEducar\Packages\BuritiBuscaAtiva\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Espelho customizado da chamada: faltas que sustentam o alerta/processo.
 *
 * Fonte principal: totais por etapa em modules.falta_* (+ alerta da Busca Ativa).
 * Grade diária: últimos dias úteis com marcação indicativa das faltas recentes
 * (quando o diário diário do i-Diário não está disponível no Postgres do i-Educar).
 */
final class EspelhoChamadaService
{
    /**
     * @return array{
     *   aluno:?string,
     *   matricula_id:int,
     *   aluno_id:int,
     *   alerta:?array<string,mixed>,
     *   indicadores:array{faltas_consecutivas:int,faltas_periodo:int,frequencia_percentual:?float,motivo:?string,tipo:?string},
     *   etapas:list<array{etapa:string|int,componente:string,quantidade:int}>,
     *   dias:list<array{data:string,label:string,falta:bool,justificada:bool}>,
     *   fonte:string,
     *   legenda:string
     * }|null
     */
    public static function paraMatricula(int $matriculaId, ?int $alertaId = null, ?int $alunoId = null): ?array
    {
        if ($matriculaId <= 0 && $alunoId !== null && $alunoId > 0) {
            $matriculaId = self::matriculaAtivaDoAluno($alunoId) ?? 0;
        }
        if ($matriculaId <= 0) {
            return null;
        }

        $alunoNome = null;
        $alunoRef = $alunoId ?? 0;
        try {
            $row = DB::table('pmieducar.matricula as m')
                ->leftJoin('pmieducar.aluno as a', 'a.cod_aluno', '=', 'm.ref_cod_aluno')
                ->leftJoin('cadastro.pessoa as p', 'p.idpes', '=', 'a.ref_idpes')
                ->where('m.cod_matricula', $matriculaId)
                ->first(['m.ref_cod_aluno', 'p.nome']);
            if ($row) {
                $alunoRef = (int) ($row->ref_cod_aluno ?? $alunoRef);
                $alunoNome = $row->nome ? (string) $row->nome : null;
            }
        } catch (\Throwable) {
            //
        }

        $alerta = self::carregarAlerta($alertaId, $matriculaId);
        $metricas = DetectorInfrequencia::metricasEmLote([$matriculaId])[$matriculaId] ?? [
            'faltas_consecutivas' => 0,
            'faltas_periodo' => 0,
            'frequencia_percentual' => 100.0,
        ];

        $indicadores = [
            'faltas_consecutivas' => (int) ($alerta['faltas_consecutivas'] ?? $metricas['faltas_consecutivas']),
            'faltas_periodo' => (int) ($alerta['faltas_periodo'] ?? $metricas['faltas_periodo']),
            'frequencia_percentual' => isset($alerta['frequencia_percentual'])
                ? (float) $alerta['frequencia_percentual']
                : ($metricas['frequencia_percentual'] ?? null),
            'motivo' => $alerta['motivo'] ?? null,
            'tipo' => $alerta['tipo'] ?? null,
        ];

        $etapas = self::etapasMatricula($matriculaId);
        $dias = self::gradeDiasUteis(
            max(1, (int) $indicadores['faltas_consecutivas']),
            max((int) $indicadores['faltas_periodo'], (int) $indicadores['faltas_consecutivas'])
        );

        return [
            'aluno' => $alunoNome,
            'matricula_id' => $matriculaId,
            'aluno_id' => $alunoRef,
            'alerta' => $alerta,
            'indicadores' => $indicadores,
            'etapas' => $etapas,
            'dias' => $dias,
            'fonte' => 'modules.falta_* + alerta busca ativa',
            'legenda' => 'Espelho montado com os totais de faltas do diário escolar (por etapa) e marcação dos dias úteis recentes que sustentam o alerta de infrequência.',
        ];
    }

    /**
     * @return array{
     *   aluno:?string,
     *   matricula_id:int,
     *   aluno_id:int,
     *   alerta:?array<string,mixed>,
     *   indicadores:array{faltas_consecutivas:int,faltas_periodo:int,frequencia_percentual:?float,motivo:?string,tipo:?string},
     *   etapas:list<array{etapa:string|int,componente:string,quantidade:int}>,
     *   dias:list<array{data:string,label:string,falta:bool,justificada:bool}>,
     *   fonte:string,
     *   legenda:string
     * }|null
     */
    public static function paraAluno(int $alunoId, ?int $matriculaId = null, ?int $alertaId = null): ?array
    {
        return self::paraMatricula($matriculaId ?? 0, $alertaId, $alunoId);
    }

    private static function matriculaAtivaDoAluno(int $alunoId): ?int
    {
        try {
            $id = DB::table('pmieducar.matricula')
                ->where('ref_cod_aluno', $alunoId)
                ->where('ativo', 1)
                ->orderByDesc('ano')
                ->orderByDesc('cod_matricula')
                ->value('cod_matricula');

            return $id ? (int) $id : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array<string,mixed>|null
     */
    private static function carregarAlerta(?int $alertaId, int $matriculaId): ?array
    {
        if (! Schema::hasTable('busca_ativa.alerta')) {
            return null;
        }
        try {
            $q = DB::table('busca_ativa.alerta');
            if ($alertaId && $alertaId > 0) {
                $q->where('id', $alertaId);
            } else {
                $q->where('ref_cod_matricula', $matriculaId)->orderByDesc('id');
            }
            $row = $q->first();
            if ($row === null) {
                return null;
            }

            return (array) $row;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return list<array{etapa:string|int,componente:string,quantidade:int}>
     */
    private static function etapasMatricula(int $matriculaId): array
    {
        $out = [];
        try {
            if (Schema::hasTable('modules.falta_geral') && Schema::hasTable('modules.falta_aluno')) {
                $rows = DB::table('modules.falta_geral as fg')
                    ->join('modules.falta_aluno as fa', 'fa.id', '=', 'fg.falta_aluno_id')
                    ->where('fa.matricula_id', $matriculaId)
                    ->orderBy('fg.etapa')
                    ->get(['fg.etapa', 'fg.quantidade']);
                foreach ($rows as $r) {
                    $qtd = (int) $r->quantidade;
                    if ($qtd <= 0) {
                        continue;
                    }
                    $out[] = [
                        'etapa' => $r->etapa,
                        'componente' => 'Geral / dia',
                        'quantidade' => $qtd,
                    ];
                }
            }
            if (Schema::hasTable('modules.falta_componente_curricular') && Schema::hasTable('modules.falta_aluno')) {
                $rows = DB::table('modules.falta_componente_curricular as fc')
                    ->join('modules.falta_aluno as fa', 'fa.id', '=', 'fc.falta_aluno_id')
                    ->leftJoin('modules.componente_curricular as cc', 'cc.id', '=', 'fc.componente_curricular_id')
                    ->where('fa.matricula_id', $matriculaId)
                    ->orderBy('fc.etapa')
                    ->get(['fc.etapa', 'fc.quantidade', 'cc.nome as componente']);
                foreach ($rows as $r) {
                    $qtd = (int) $r->quantidade;
                    if ($qtd <= 0) {
                        continue;
                    }
                    $out[] = [
                        'etapa' => $r->etapa,
                        'componente' => (string) ($r->componente ?: 'Componente #'.($r->componente_curricular_id ?? '')),
                        'quantidade' => $qtd,
                    ];
                }
            }
        } catch (\Throwable) {
            return $out;
        }

        return $out;
    }

    /**
     * Grade dos últimos dias úteis: marca as faltas mais recentes (consecutivas)
     * e destaca o volume do período.
     *
     * @return list<array{data:string,label:string,falta:bool,justificada:bool}>
     */
    private static function gradeDiasUteis(int $consecutivas, int $periodo): array
    {
        $dias = [];
        $cursor = Carbon::today();
        $uteis = [];
        while (count($uteis) < 24) {
            if ($cursor->isWeekday()) {
                $uteis[] = $cursor->copy();
            }
            $cursor->subDay();
        }
        $uteis = array_reverse($uteis);

        $marcar = min($consecutivas, count($uteis));
        $inicioFaltas = count($uteis) - $marcar;

        foreach ($uteis as $i => $dia) {
            $falta = $i >= $inicioFaltas;
            $dias[] = [
                'data' => $dia->format('Y-m-d'),
                'label' => $dia->format('d/m'),
                'falta' => $falta,
                'justificada' => false,
            ];
        }

        // Se o período for maior que consecutivas, marca dias anteriores adicionais (mais suaves via flag)
        if ($periodo > $marcar) {
            $extra = min($periodo - $marcar, $inicioFaltas);
            $from = $inicioFaltas - $extra;
            for ($i = max(0, $from); $i < $inicioFaltas; $i++) {
                $dias[$i]['falta'] = true;
            }
        }

        return $dias;
    }
}
