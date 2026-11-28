<?php

namespace iEducar\Packages\BuritiBuscaAtiva\Services;

use iEducar\Packages\BuritiBuscaAtiva\Support\EtapasProcesso;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Painel gerencial da Busca Ativa: KPIs, séries para gráficos, qualidade e lista de alunos.
 */
final class RelatorioGestao
{
    /**
     * @return array{
     *   kpis: list<array{rotulo:string,valor:string|int,icone:string,tom:string,ajuda?:string}>,
     *   qualidade: list<array{rotulo:string,valor:string,meta:string,tom:string,pct:float}>,
     *   porTipo: list<array{chave:string,total:int}>,
     *   porSeveridade: list<array{chave:string,total:int}>,
     *   porStatusAlerta: list<array{chave:string,total:int}>,
     *   porStatusProcesso: list<array{chave:string,total:int}>,
     *   porEtapa: list<array{chave:string,rotulo:string,total:int}>,
     *   porEscola: list<array{chave:string,total:int}>,
     *   porResultado: list<array{chave:string,total:int}>,
     *   alunos: list<array<string,mixed>>,
     *   leitura: list<string>,
     *   atualizadoEm: string
     * }
     */
    public static function montar(int $limiteAlunos = 500): array
    {
        $porTipo = self::agrupar('busca_ativa.alerta', 'tipo');
        $porSeveridade = self::agrupar('busca_ativa.alerta', 'severidade');
        $porStatusAlerta = self::agrupar('busca_ativa.alerta', 'status');
        $porStatusProcesso = self::agrupar('busca_ativa.processo', 'status');
        $porResultado = self::agrupar('busca_ativa.processo', 'resultado', true);

        $porEtapaRaw = Schema::hasTable('busca_ativa.processo')
            ? DB::table('busca_ativa.processo')
                ->where('status', 'aberto')
                ->select('etapa_atual', DB::raw('count(*) as total'))
                ->groupBy('etapa_atual')
                ->pluck('total', 'etapa_atual')
                ->all()
            : [];
        $porEtapa = [];
        foreach (EtapasProcesso::todas() as $etapa) {
            $porEtapa[] = [
                'chave' => $etapa,
                'rotulo' => EtapasProcesso::rotulo($etapa),
                'total' => (int) ($porEtapaRaw[$etapa] ?? 0),
            ];
        }

        $porEscola = [];
        if (Schema::hasTable('busca_ativa.alerta')) {
            $rows = DB::table('busca_ativa.alerta as a')
                ->leftJoin('pmieducar.escola as e', 'e.cod_escola', '=', 'a.ref_cod_escola')
                ->leftJoin('cadastro.juridica as j', 'j.idpes', '=', 'e.ref_idpes')
                ->where('a.status', 'aberto')
                ->select(DB::raw("COALESCE(NULLIF(j.fantasia, ''), 'Sem escola') as escola"), DB::raw('count(*) as total'))
                ->groupBy('escola')
                ->orderByDesc('total')
                ->limit(12)
                ->get();
            foreach ($rows as $r) {
                $porEscola[] = ['chave' => (string) $r->escola, 'total' => (int) $r->total];
            }
        }

        $alertasAbertos = self::contar('busca_ativa.alerta', ['status' => 'aberto']);
        $alertasTotal = self::contar('busca_ativa.alerta');
        $alertasAlta = self::contar('busca_ativa.alerta', ['status' => 'aberto', 'severidade' => 'alta']);
        $processosAbertos = self::contar('busca_ativa.processo', ['status' => 'aberto']);
        $processosEncerrados = self::contar('busca_ativa.processo', ['status' => 'encerrado']);
        $processosTotal = $processosAbertos + $processosEncerrados;
        $retornos = Schema::hasTable('busca_ativa.processo')
            ? (int) DB::table('busca_ativa.processo')->where('resultado', 'retorno')->count()
            : 0;
        $foraPrazo = 0;
        if (Schema::hasTable('busca_ativa.processo')) {
            $foraPrazo = (int) DB::table('busca_ativa.processo')
                ->where('status', 'aberto')
                ->whereNotNull('prazo_contato_em')
                ->where('prazo_contato_em', '<', now())
                ->count();
        }
        $alunosUnicos = Schema::hasTable('busca_ativa.alerta')
            ? (int) DB::table('busca_ativa.alerta')
                ->where('status', 'aberto')
                ->selectRaw('count(distinct ref_cod_aluno) as total')
                ->value('total')
            : 0;

        $taxaResolucao = $alertasTotal > 0
            ? round((($alertasTotal - $alertasAbertos) / $alertasTotal) * 100, 1)
            : 0.0;
        $taxaRetorno = $processosEncerrados > 0
            ? round(($retornos / $processosEncerrados) * 100, 1)
            : 0.0;
        $taxaNoPrazo = $processosAbertos > 0
            ? round((($processosAbertos - $foraPrazo) / $processosAbertos) * 100, 1)
            : 100.0;
        $coberturaProcesso = $alertasAbertos > 0
            ? round(min(100, ($processosAbertos / max(1, $alertasAbertos)) * 100), 1)
            : ($processosAbertos > 0 ? 100.0 : 0.0);

        $kpis = [
            [
                'rotulo' => 'Alunos em risco',
                'valor' => $alunosUnicos,
                'icone' => 'fa-users',
                'tom' => $alunosUnicos > 0 ? '#9a3412' : 'var(--ok)',
                'ajuda' => 'Alunos distintos com alerta aberto',
            ],
            [
                'rotulo' => 'Alertas abertos',
                'valor' => $alertasAbertos,
                'icone' => 'fa-bell',
                'tom' => $alertasAbertos > 0 ? 'var(--ambar)' : 'var(--ok)',
                'ajuda' => 'Infrequência ainda sem resolução',
            ],
            [
                'rotulo' => 'Severidade alta',
                'valor' => $alertasAlta,
                'icone' => 'fa-exclamation-triangle',
                'tom' => $alertasAlta > 0 ? 'var(--erro)' : 'var(--ok)',
                'ajuda' => 'Prioridade imediata da rede',
            ],
            [
                'rotulo' => 'Processos abertos',
                'valor' => $processosAbertos,
                'icone' => 'fa-folder-open',
                'tom' => 'var(--destaque)',
                'ajuda' => 'Acompanhamentos em andamento',
            ],
            [
                'rotulo' => 'Fora do prazo',
                'valor' => $foraPrazo,
                'icone' => 'fa-clock-o',
                'tom' => $foraPrazo > 0 ? 'var(--erro)' : 'var(--ok)',
                'ajuda' => 'Contato inicial vencido',
            ],
            [
                'rotulo' => 'Retornos às aulas',
                'valor' => $retornos,
                'icone' => 'fa-graduation-cap',
                'tom' => 'var(--ok)',
                'ajuda' => 'Encerramentos com resultado retorno',
            ],
        ];

        $qualidade = [
            [
                'rotulo' => 'Resolução de alertas',
                'valor' => $taxaResolucao.'%',
                'meta' => 'Meta sugerida ≥ 70%',
                'tom' => $taxaResolucao >= 70 ? 'ok' : ($taxaResolucao >= 40 ? 'warn' : 'fail'),
                'pct' => min(100.0, $taxaResolucao),
            ],
            [
                'rotulo' => 'Processos no prazo',
                'valor' => $taxaNoPrazo.'%',
                'meta' => 'Contato dentro do limite configurado',
                'tom' => $taxaNoPrazo >= 85 ? 'ok' : ($taxaNoPrazo >= 60 ? 'warn' : 'fail'),
                'pct' => min(100.0, $taxaNoPrazo),
            ],
            [
                'rotulo' => 'Retorno escolar',
                'valor' => $taxaRetorno.'%',
                'meta' => 'Entre processos encerrados',
                'tom' => $taxaRetorno >= 60 ? 'ok' : ($taxaRetorno >= 30 ? 'warn' : 'fail'),
                'pct' => min(100.0, $taxaRetorno),
            ],
            [
                'rotulo' => 'Cobertura processual',
                'valor' => $coberturaProcesso.'%',
                'meta' => 'Processos abertos frente a alertas abertos',
                'tom' => $coberturaProcesso >= 80 ? 'ok' : ($coberturaProcesso >= 50 ? 'warn' : 'fail'),
                'pct' => min(100.0, $coberturaProcesso),
            ],
        ];

        $alunos = self::listaAlunos($limiteAlunos);

        $leitura = self::textosLeitura(
            $alunosUnicos,
            $alertasAbertos,
            $alertasAlta,
            $foraPrazo,
            $taxaResolucao,
            $taxaRetorno,
            $taxaNoPrazo,
            $processosTotal,
        );

        return [
            'kpis' => $kpis,
            'qualidade' => $qualidade,
            'porTipo' => $porTipo,
            'porSeveridade' => $porSeveridade,
            'porStatusAlerta' => $porStatusAlerta,
            'porStatusProcesso' => $porStatusProcesso,
            'porEtapa' => $porEtapa,
            'porEscola' => $porEscola,
            'porResultado' => $porResultado,
            'alunos' => $alunos,
            'leitura' => $leitura,
            'atualizadoEm' => now()->format('d/m/Y H:i'),
        ];
    }

    /**
     * @return list<array<string,mixed>>
     */
    public static function listaAlunos(int $limite = 2000): array
    {
        if (! Schema::hasTable('busca_ativa.alerta')) {
            return [];
        }

        $rows = DB::table('busca_ativa.alerta as a')
            ->leftJoin('pmieducar.aluno as al', 'al.cod_aluno', '=', 'a.ref_cod_aluno')
            ->leftJoin('cadastro.pessoa as p', 'p.idpes', '=', 'al.ref_idpes')
            ->leftJoin('pmieducar.escola as e', 'e.cod_escola', '=', 'a.ref_cod_escola')
            ->leftJoin('cadastro.juridica as j', 'j.idpes', '=', 'e.ref_idpes')
            ->leftJoin('busca_ativa.processo as pr', function ($join): void {
                $join->on('pr.alerta_id', '=', 'a.id')
                    ->where('pr.status', '=', 'aberto');
            })
            ->orderByRaw("CASE WHEN a.status = 'aberto' THEN 0 ELSE 1 END")
            ->orderByRaw("CASE WHEN a.severidade = 'alta' THEN 0 WHEN a.severidade = 'media' THEN 1 ELSE 2 END")
            ->orderByDesc('a.detectado_em')
            ->limit($limite)
            ->get([
                'a.id as alerta_id',
                'a.ref_cod_aluno',
                'a.ref_cod_matricula',
                'a.tipo',
                'a.severidade',
                'a.status as alerta_status',
                'a.faltas_consecutivas',
                'a.faltas_periodo',
                'a.frequencia_percentual',
                'a.motivo',
                'a.detectado_em',
                'a.resolvido_em',
                'p.nome as aluno_nome',
                'j.fantasia as escola_nome',
                'pr.id as processo_id',
                'pr.etapa_atual',
                'pr.status as processo_status',
                'pr.prazo_contato_em',
                'pr.resultado',
            ]);

        $out = [];
        foreach ($rows as $r) {
            $foraPrazo = $r->prazo_contato_em
                && ($r->processo_status ?? '') === 'aberto'
                && strtotime((string) $r->prazo_contato_em) < time();
            $out[] = [
                'alerta_id' => (int) $r->alerta_id,
                'aluno_id' => (int) $r->ref_cod_aluno,
                'aluno' => (string) ($r->aluno_nome ?: 'Aluno #'.$r->ref_cod_aluno),
                'matricula' => (int) $r->ref_cod_matricula,
                'escola' => (string) ($r->escola_nome ?: '—'),
                'tipo' => (string) ($r->tipo ?? '—'),
                'severidade' => (string) ($r->severidade ?? 'media'),
                'alerta_status' => (string) ($r->alerta_status ?? '—'),
                'faltas_consecutivas' => $r->faltas_consecutivas !== null ? (int) $r->faltas_consecutivas : null,
                'faltas_periodo' => $r->faltas_periodo !== null ? (int) $r->faltas_periodo : null,
                'frequencia' => $r->frequencia_percentual !== null ? (float) $r->frequencia_percentual : null,
                'motivo' => (string) ($r->motivo ?? ''),
                'detectado_em' => (string) ($r->detectado_em ?? ''),
                'resolvido_em' => $r->resolvido_em ? (string) $r->resolvido_em : '',
                'processo_id' => $r->processo_id ? (int) $r->processo_id : null,
                'etapa' => $r->etapa_atual ? EtapasProcesso::rotulo((string) $r->etapa_atual) : '—',
                'processo_status' => (string) ($r->processo_status ?? '—'),
                'prazo_contato' => $r->prazo_contato_em ? (string) $r->prazo_contato_em : '',
                'fora_prazo' => (bool) $foraPrazo,
                'resultado' => (string) ($r->resultado ?? ''),
            ];
        }

        return $out;
    }

    /**
     * @return list<list<scalar|null>>
     */
    public static function linhasExcelAlunos(array $alunos): array
    {
        $linhas = [];
        foreach ($alunos as $a) {
            $linhas[] = [
                $a['alerta_id'] ?? '',
                $a['aluno_id'] ?? '',
                $a['aluno'] ?? '',
                $a['matricula'] ?? '',
                $a['escola'] ?? '',
                $a['tipo'] ?? '',
                $a['severidade'] ?? '',
                $a['alerta_status'] ?? '',
                $a['faltas_consecutivas'] ?? '',
                $a['faltas_periodo'] ?? '',
                $a['frequencia'] !== null ? $a['frequencia'].'%' : '',
                $a['motivo'] ?? '',
                $a['detectado_em'] ?? '',
                $a['processo_id'] ?? '',
                $a['etapa'] ?? '',
                $a['processo_status'] ?? '',
                $a['prazo_contato'] ?? '',
                ! empty($a['fora_prazo']) ? 'Sim' : 'Não',
                $a['resultado'] ?? '',
            ];
        }

        return $linhas;
    }

    /** @return list<string> */
    public static function cabecalhosExcelAlunos(): array
    {
        return [
            'Alerta ID',
            'Aluno ID',
            'Aluno',
            'Matrícula',
            'Escola',
            'Tipo de alerta',
            'Severidade',
            'Status alerta',
            'Faltas consecutivas',
            'Faltas no período',
            'Frequência %',
            'Motivo',
            'Detectado em',
            'Processo ID',
            'Etapa',
            'Status processo',
            'Prazo contato',
            'Fora do prazo',
            'Resultado',
        ];
    }

    /**
     * @return list<array{chave:string,total:int}>
     */
    private static function agrupar(string $tabela, string $coluna, bool $ignorarNulos = false): array
    {
        if (! Schema::hasTable($tabela)) {
            return [];
        }
        $q = DB::table($tabela)->select($coluna, DB::raw('count(*) as total'))->groupBy($coluna);
        if ($ignorarNulos) {
            $q->whereNotNull($coluna)->where($coluna, '<>', '');
        }
        $out = [];
        foreach ($q->get() as $r) {
            $chave = (string) ($r->{$coluna} ?? '—');
            if ($chave === '') {
                $chave = '—';
            }
            $out[] = ['chave' => $chave, 'total' => (int) $r->total];
        }
        usort($out, static fn (array $a, array $b): int => $b['total'] <=> $a['total']);

        return $out;
    }

    /** @param  array<string, mixed>  $where */
    private static function contar(string $tabela, array $where = []): int
    {
        if (! Schema::hasTable($tabela)) {
            return 0;
        }
        $q = DB::table($tabela);
        foreach ($where as $col => $val) {
            $q->where($col, $val);
        }

        return (int) $q->count();
    }

    /**
     * @return list<string>
     */
    private static function textosLeitura(
        int $alunos,
        int $alertasAbertos,
        int $alta,
        int $foraPrazo,
        float $resolucao,
        float $retorno,
        float $noPrazo,
        int $processosTotal,
    ): array {
        $textos = [];
        if ($alunos === 0 && $processosTotal === 0) {
            $textos[] = 'Ainda não há sinais de risco registrados. Rode a detecção após a chamada ou valide se os cursos estão marcados para Busca Ativa.';
            $textos[] = 'Uma gestão educacional de qualidade acompanha a frequência diariamente e age nos primeiros sinais — não só no fim do bimestre.';

            return $textos;
        }

        $textos[] = $alunos === 1
            ? 'Há 1 estudante com alerta aberto. Priorize o contato com a família e o registro da etapa no processo.'
            : "Há {$alunos} estudantes com alerta aberto. Organize a fila por severidade e escola para não perder o prazo de contato.";

        if ($alta > 0) {
            $textos[] = "{$alta} alerta(s) de severidade alta pedem ação imediata da equipe da unidade e, se necessário, encaminhamento à SME.";
        }
        if ($foraPrazo > 0) {
            $textos[] = "{$foraPrazo} processo(s) ultrapassaram o prazo de contato. Revise responsáveis e canais (telefone, visita, apps da família).";
        }

        if ($resolucao >= 70) {
            $textos[] = "A taxa de resolução de alertas está em {$resolucao}% — patamar saudável. Mantenha o ritmo de fechamento com evidência no processo.";
        } elseif ($alertasAbertos > 0) {
            $textos[] = "A resolução de alertas está em {$resolucao}%. Reforce a rotina: detectar → contatar → registrar etapa → encerrar com resultado.";
        }

        if ($retorno > 0) {
            $textos[] = "Entre os encerramentos, {$retorno}% resultaram em retorno às aulas — principal indicador de efetividade da Busca Ativa.";
        }

        $textos[] = "Processos no prazo: {$noPrazo}%. Use este painel na reunião pedagógica semanal para alinhar escola, orientação e assistência.";

        return $textos;
    }
}
