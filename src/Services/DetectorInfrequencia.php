<?php

namespace iEducar\Packages\BuritiBuscaAtiva\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class DetectorInfrequencia
{
    /**
     * Analisa matrículas ativas em cursos/turmas elegíveis e gera alertas.
     *
     * @return array{analisados:int,alertas:int,processos:int}
     */
    public static function executar(?int $instituicao = null, ?int $ano = null): array
    {
        $instituicao ??= ConfiguracaoService::instituicaoPadrao();
        $ano ??= (int) date('Y');
        $cfg = ConfiguracaoService::obter($instituicao);

        if (! ($cfg['ativo'] ?? true) || ! Schema::hasTable('busca_ativa.alerta')) {
            return ['analisados' => 0, 'alertas' => 0, 'processos' => 0];
        }

        $somenteMarcados = (bool) ($cfg['somente_cursos_marcados'] ?? true);
        $limiteConsec = (int) $cfg['faltas_consecutivas_alerta'];
        $limiteMensal = (int) $cfg['faltas_mensais_alerta'];
        $freqMin = (float) $cfg['frequencia_minima_percentual'];

        $q = DB::table('pmieducar.matricula as m')
            ->join('pmieducar.matricula_turma as mt', function ($join): void {
                $join->on('mt.ref_cod_matricula', '=', 'm.cod_matricula')
                    ->where('mt.ativo', 1);
            })
            ->join('pmieducar.turma as t', 't.cod_turma', '=', 'mt.ref_cod_turma')
            ->join('pmieducar.curso as c', 'c.cod_curso', '=', 'm.ref_cod_curso')
            ->join('pmieducar.escola as e', 'e.cod_escola', '=', 'm.ref_ref_cod_escola')
            ->where('m.ativo', 1)
            ->whereIn('m.aprovado', [1, 2, 3, 12, 13, 14])
            ->where(function ($w) use ($ano): void {
                $w->where('m.ano', $ano)->orWhere('t.ano', $ano);
            })
            ->where('e.ref_cod_instituicao', $instituicao)
            ->select([
                'm.cod_matricula',
                'm.ref_cod_aluno',
                'mt.ref_cod_turma',
                'm.ref_ref_cod_escola as cod_escola',
                'm.ref_cod_curso as cod_curso',
                'c.busca_ativa as curso_busca_ativa',
            ]);

        if ($somenteMarcados && Schema::hasColumn('pmieducar.curso', 'busca_ativa')) {
            $q->where('c.busca_ativa', true);
        }

        $matriculas = $q->distinct()->get();
        $ids = $matriculas->pluck('cod_matricula')->map(fn ($id) => (int) $id)->unique()->values()->all();
        $metricasMap = self::metricasEmLote($ids);
        $alertasAbertos = DB::table('busca_ativa.alerta')
            ->whereIn('ref_cod_matricula', $ids ?: [0])
            ->where('status', 'aberto')
            ->get(['ref_cod_matricula', 'tipo'])
            ->groupBy(fn ($r) => (int) $r->ref_cod_matricula)
            ->map(fn ($rows) => $rows->pluck('tipo')->all());

        $alertas = 0;
        $processos = 0;

        foreach ($matriculas as $mat) {
            $matriculaId = (int) $mat->cod_matricula;
            $metricas = $metricasMap[$matriculaId] ?? [
                'faltas_consecutivas' => 0,
                'faltas_periodo' => 0,
                'frequencia_percentual' => 100.0,
            ];
            $tipo = null;
            $severidade = 'media';
            $motivo = null;

            if ($metricas['faltas_consecutivas'] >= $limiteConsec) {
                $tipo = 'faltas_consecutivas';
                $severidade = 'alta';
                $motivo = sprintf(
                    'Aluno com %d faltas consecutivas (limite %d).',
                    $metricas['faltas_consecutivas'],
                    $limiteConsec
                );
            } elseif ($metricas['faltas_periodo'] >= $limiteMensal) {
                $tipo = 'faltas_periodo';
                $severidade = 'media';
                $motivo = sprintf(
                    'Aluno com %d faltas no período (limite %d).',
                    $metricas['faltas_periodo'],
                    $limiteMensal
                );
            } elseif ($metricas['frequencia_percentual'] !== null && $metricas['frequencia_percentual'] < $freqMin) {
                $tipo = 'frequencia_baixa';
                $severidade = 'media';
                $motivo = sprintf(
                    'Frequência %.1f%% abaixo do mínimo %.1f%%.',
                    $metricas['frequencia_percentual'],
                    $freqMin
                );
            }

            if ($tipo === null) {
                continue;
            }

            $tiposAbertos = $alertasAbertos->get($matriculaId, []);
            if (in_array($tipo, $tiposAbertos, true)) {
                continue;
            }

            $alertaId = (int) DB::table('busca_ativa.alerta')->insertGetId([
                'ref_cod_instituicao' => $instituicao,
                'ref_cod_escola' => $mat->cod_escola,
                'ref_cod_curso' => $mat->cod_curso,
                'ref_cod_turma' => $mat->ref_cod_turma,
                'ref_cod_matricula' => $matriculaId,
                'ref_cod_aluno' => $mat->ref_cod_aluno,
                'tipo' => $tipo,
                'severidade' => $severidade,
                'status' => 'aberto',
                'faltas_consecutivas' => $metricas['faltas_consecutivas'],
                'faltas_periodo' => $metricas['faltas_periodo'],
                'frequencia_percentual' => $metricas['frequencia_percentual'],
                'motivo' => $motivo,
                'metadados' => json_encode(['ano' => $ano], JSON_UNESCAPED_UNICODE),
                'detectado_em' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $alertas++;
            $tiposAbertos[] = $tipo;
            $alertasAbertos[$matriculaId] = $tiposAbertos;

            $alerta = [
                'id' => $alertaId,
                'ref_cod_instituicao' => $instituicao,
                'ref_cod_escola' => $mat->cod_escola,
                'ref_cod_matricula' => $matriculaId,
                'ref_cod_aluno' => $mat->ref_cod_aluno,
                'tipo' => $tipo,
                'motivo' => $motivo,
            ];

            if ($cfg['gerar_alerta_atendimento'] ?? true) {
                $pocId = AtendimentoBridge::publicarAlerta($alerta);
                if ($pocId) {
                    DB::table('busca_ativa.alerta')->where('id', $alertaId)->update([
                        'atendimento_poc_id' => $pocId,
                        'updated_at' => now(),
                    ]);
                }
            }

            if ($cfg['abrir_processo_automatico'] ?? true) {
                $pid = ProcessoService::abrirDeAlerta($alerta, $cfg);
                if ($pid) {
                    $processos++;
                }
            }
        }

        if ($alertas > 0 && class_exists(\iEducar\Packages\BuritiLagunaAmbiente\Support\Notificador::class)) {
            $msg = sprintf(
                'Busca Ativa: %d novo(s) alerta(s) de infrequência e %d processo(s) aberto(s) (ano %d).',
                $alertas,
                $processos,
                $ano
            );
            \iEducar\Packages\BuritiLagunaAmbiente\Support\Notificador::paraProcesso(
                \iEducar\Packages\BuritiBuscaAtiva\Support\Processos::ALERTAS,
                $msg,
                '/busca-ativa/alertas',
                null,
                \App\Models\NotificationType::ACTIVE_LOOKING,
            );
            if (class_exists(\iEducar\Packages\BuritiAtendimentoEstudante\Support\Processos::class)) {
                \iEducar\Packages\BuritiLagunaAmbiente\Support\Notificador::paraProcesso(
                    \iEducar\Packages\BuritiAtendimentoEstudante\Support\Processos::FILA,
                    $msg.' Itens na fila de atendimento.',
                    '/atendimento-estudante/fila',
                    null,
                    \App\Models\NotificationType::ACTIVE_LOOKING,
                );
            }
        }

        return [
            'analisados' => $matriculas->count(),
            'alertas' => $alertas,
            'processos' => $processos,
        ];
    }

    /**
     * @param  list<int>  $matriculaIds
     * @return array<int, array{faltas_consecutivas:int,faltas_periodo:int,frequencia_percentual:?float}>
     */
    public static function metricasEmLote(array $matriculaIds): array
    {
        $out = [];
        foreach ($matriculaIds as $id) {
            $out[$id] = [
                'faltas_consecutivas' => 0,
                'faltas_periodo' => 0,
                'frequencia_percentual' => 100.0,
            ];
        }

        if ($matriculaIds === []) {
            return $out;
        }

        if (Schema::hasTable('modules.falta_aluno') && Schema::hasTable('modules.falta_geral')) {
            $rows = DB::table('modules.falta_geral as fg')
                ->join('modules.falta_aluno as fa', 'fa.id', '=', 'fg.falta_aluno_id')
                ->whereIn('fa.matricula_id', $matriculaIds)
                ->groupBy('fa.matricula_id')
                ->select('fa.matricula_id', DB::raw('COALESCE(SUM(fg.quantidade),0) as total'))
                ->get();
            foreach ($rows as $row) {
                $id = (int) $row->matricula_id;
                $total = (int) $row->total;
                $out[$id]['faltas_periodo'] = max($out[$id]['faltas_periodo'], $total);
                $out[$id]['faltas_consecutivas'] = max($out[$id]['faltas_consecutivas'], min($total, 30));
            }
        }

        if (Schema::hasTable('modules.falta_componente_curricular') && Schema::hasTable('modules.falta_aluno')) {
            $rows = DB::table('modules.falta_componente_curricular as fc')
                ->join('modules.falta_aluno as fa', 'fa.id', '=', 'fc.falta_aluno_id')
                ->whereIn('fa.matricula_id', $matriculaIds)
                ->groupBy('fa.matricula_id')
                ->select('fa.matricula_id', DB::raw('COALESCE(SUM(fc.quantidade),0) as total'))
                ->get();
            foreach ($rows as $row) {
                $id = (int) $row->matricula_id;
                $total = (int) $row->total;
                $out[$id]['faltas_periodo'] = max($out[$id]['faltas_periodo'], $total);
                $out[$id]['faltas_consecutivas'] = max($out[$id]['faltas_consecutivas'], min($total, 30));
            }
        }

        foreach ($out as $id => $m) {
            $out[$id]['frequencia_percentual'] = $m['faltas_periodo'] > 0
                ? max(0, round(100 - ($m['faltas_periodo'] * 2.5), 1))
                : 100.0;
        }

        return $out;
    }

    /**
     * @return array{faltas_consecutivas:int,faltas_periodo:int,frequencia_percentual:?float}
     */
    public static function metricasMatricula(int $matriculaId): array
    {
        return self::metricasEmLote([$matriculaId])[$matriculaId];
    }
}
