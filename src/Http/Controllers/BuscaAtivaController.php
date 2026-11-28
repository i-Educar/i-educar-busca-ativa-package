<?php

namespace iEducar\Packages\BuritiBuscaAtiva\Http\Controllers;

use iEducar\Packages\BuritiBuscaAtiva\Services\ConfiguracaoService;
use iEducar\Packages\BuritiBuscaAtiva\Services\DetectorInfrequencia;
use iEducar\Packages\BuritiBuscaAtiva\Services\ProcessoService;
use iEducar\Packages\BuritiBuscaAtiva\Services\RelatorioGestao;
use iEducar\Packages\BuritiBuscaAtiva\Support\Acesso;
use iEducar\Packages\BuritiBuscaAtiva\Support\EtapasProcesso;
use iEducar\Packages\BuritiBuscaAtiva\Support\Processos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class BuscaAtivaController extends Controller
{
    public function index(): View
    {
        abort_unless(Acesso::pode(Processos::PAINEL), 403);

        $alertasAbertos = Schema::hasTable('busca_ativa.alerta')
            ? (int) DB::table('busca_ativa.alerta')->where('status', 'aberto')->count()
            : 0;
        $processosAbertos = Schema::hasTable('busca_ativa.processo')
            ? (int) DB::table('busca_ativa.processo')->where('status', 'aberto')->count()
            : 0;
        $cursosMarcados = Schema::hasColumn('pmieducar.curso', 'busca_ativa')
            ? (int) DB::table('pmieducar.curso')->where('busca_ativa', true)->where('ativo', 1)->count()
            : 0;
        $cfg = ConfiguracaoService::obter();

        $ultimos = Schema::hasTable('busca_ativa.alerta')
            ? DB::table('busca_ativa.alerta as a')
                ->leftJoin('pmieducar.aluno as al', 'al.cod_aluno', '=', 'a.ref_cod_aluno')
                ->leftJoin('cadastro.pessoa as p', 'p.idpes', '=', 'al.ref_idpes')
                ->orderByDesc('a.detectado_em')
                ->limit(8)
                ->get(['a.*', 'p.nome as aluno_nome'])
            : collect();

        $analise = [];
        foreach ($ultimos as $a) {
            $analise[] = [
                'titulo' => $a->aluno_nome ?: ('Aluno #'.$a->ref_cod_aluno),
                'meta' => ($a->tipo ?? '—').' · '.($a->motivo ?? ''),
                'badge' => $a->severidade === 'alta' ? 'Alta' : 'Média',
                'badge_tom' => $a->severidade === 'alta' ? 'fail' : 'warn',
                'url' => '/busca-ativa/alertas',
            ];
        }

        $porEtapa = [];
        if (Schema::hasTable('busca_ativa.processo')) {
            $porEtapa = DB::table('busca_ativa.processo')
                ->where('status', 'aberto')
                ->select('etapa_atual', DB::raw('count(*) as total'))
                ->groupBy('etapa_atual')
                ->pluck('total', 'etapa_atual')
                ->all();
        }

        return view('busca-ativa::painel', [
            'kpis' => [
                ['rotulo' => 'Alertas abertos', 'valor' => $alertasAbertos, 'icone' => 'fa-bell', 'tom' => $alertasAbertos ? 'var(--ambar)' : 'var(--ok)'],
                ['rotulo' => 'Processos abertos', 'valor' => $processosAbertos, 'icone' => 'fa-folder-open', 'tom' => 'var(--destaque)'],
                ['rotulo' => 'Cursos no radar', 'valor' => $cursosMarcados, 'icone' => 'fa-graduation-cap', 'tom' => 'var(--cor)'],
                ['rotulo' => 'Limite faltas', 'valor' => $cfg['faltas_consecutivas_alerta'], 'icone' => 'fa-calendar-times-o', 'tom' => '#9a3412'],
                ['rotulo' => 'Freq. mínima %', 'valor' => $cfg['frequencia_minima_percentual'], 'icone' => 'fa-percent', 'tom' => 'var(--ok)'],
            ],
            'destaques' => [
                [
                    'titulo' => $cfg['somente_cursos_marcados'] ? 'Somente cursos marcados' : 'Todos os cursos',
                    'meta' => 'Escopo da detecção',
                    'icone' => 'fa-filter',
                    'tom' => 'var(--cor)',
                    'corpo' => 'Marque a flag "Busca Ativa" no cadastro do curso para incluir suas turmas.',
                ],
                [
                    'titulo' => ($cfg['gerar_alerta_atendimento'] ?? true) ? 'Ponte com atendimento ativa' : 'Ponte com atendimento off',
                    'meta' => 'Integração 5.9',
                    'icone' => 'fa-heartbeat',
                    'tom' => 'var(--ok)',
                    'corpo' => 'Alertas geram registro na fila do módulo Atendimento ao estudante.',
                ],
                [
                    'titulo' => 'API no módulo Apps',
                    'meta' => 'Sanctum · perfil família',
                    'icone' => 'fa-mobile',
                    'tom' => 'var(--destaque)',
                    'corpo' => 'Consultas e acompanhamento familiar ficam em Apps da rede (tokens Sanctum), não nesta tela.',
                    'url' => '/configuracoes/modulos?modulo=apps-api',
                ],
            ],
            'analise' => $analise ?: [[
                'titulo' => 'Sem alertas recentes',
                'meta' => 'Execute a detecção ou aguarde novas faltas',
                'badge' => 'OK',
                'badge_tom' => 'ok',
            ]],
            'analiseTitulo' => 'Últimos alertas',
            'resumo' => 'Fluxo: detectar → alertas → processos (etapas) → retorno/encerramento. API família no módulo Apps.',
            'atalhos' => [
                ['url' => '/busca-ativa/alertas', 'icone' => 'fa-bell', 'rotulo' => 'Alertas', 'meta' => 'Infrequência'],
                ['url' => '/busca-ativa/processos', 'icone' => 'fa-folder-open', 'rotulo' => 'Processos', 'meta' => 'Etapas'],
                ['url' => '/configuracoes/modulos?modulo=busca-ativa', 'icone' => 'fa-cog', 'rotulo' => 'Regras', 'meta' => 'Limites'],
                ['url' => '/busca-ativa/relatorios', 'icone' => 'fa-bar-chart', 'rotulo' => 'Relatórios', 'meta' => 'Síntese'],
                ['url' => '/configuracoes/modulos?modulo=apps-api', 'icone' => 'fa-mobile', 'rotulo' => 'Apps API', 'meta' => 'Família / tokens'],
            ],
            'fluxoTitulo' => 'Fluxo da Busca Ativa',
            'porEtapa' => $porEtapa,
            'etapasRotulo' => array_combine(
                EtapasProcesso::todas(),
                array_map([EtapasProcesso::class, 'rotulo'], EtapasProcesso::todas())
            ),
        ]);
    }

    public function detectar(): RedirectResponse
    {
        abort_unless(Acesso::pode(Processos::ALERTAS, 'cadastra'), 403);
        $r = DetectorInfrequencia::executar();

        return redirect('/busca-ativa/alertas')->with(
            'ok',
            sprintf('Detecção concluída: %d matrículas · %d alertas · %d processos.', $r['analisados'], $r['alertas'], $r['processos'])
        );
    }

    public function alertas(): View
    {
        abort_unless(Acesso::pode(Processos::ALERTAS), 403);
        $rows = Schema::hasTable('busca_ativa.alerta')
            ? DB::table('busca_ativa.alerta as a')
                ->leftJoin('pmieducar.aluno as al', 'al.cod_aluno', '=', 'a.ref_cod_aluno')
                ->leftJoin('cadastro.pessoa as p', 'p.idpes', '=', 'al.ref_idpes')
                ->leftJoin('pmieducar.escola as e', 'e.cod_escola', '=', 'a.ref_cod_escola')
                ->leftJoin('cadastro.juridica as j', 'j.idpes', '=', 'e.ref_idpes')
                ->orderByDesc('a.detectado_em')
                ->limit(200)
                ->get(['a.*', 'p.nome as aluno_nome', 'j.fantasia as escola_nome'])
            : collect();

        return view('busca-ativa::alertas', ['alertas' => $rows]);
    }

    public function alertaDetalhe(int $id): View
    {
        abort_unless(Acesso::pode(Processos::ALERTAS), 403);
        $alerta = DB::table('busca_ativa.alerta as a')
            ->leftJoin('pmieducar.aluno as al', 'al.cod_aluno', '=', 'a.ref_cod_aluno')
            ->leftJoin('cadastro.pessoa as p', 'p.idpes', '=', 'al.ref_idpes')
            ->leftJoin('pmieducar.escola as e', 'e.cod_escola', '=', 'a.ref_cod_escola')
            ->leftJoin('cadastro.juridica as j', 'j.idpes', '=', 'e.ref_idpes')
            ->where('a.id', $id)
            ->first(['a.*', 'p.nome as aluno_nome', 'j.fantasia as escola_nome']);
        abort_if($alerta === null, 404);

        $processoId = Schema::hasTable('busca_ativa.processo')
            ? DB::table('busca_ativa.processo')->where('alerta_id', $id)->orderByDesc('id')->value('id')
            : null;

        $espelho = \iEducar\Packages\BuritiBuscaAtiva\Services\EspelhoChamadaService::paraMatricula(
            (int) ($alerta->ref_cod_matricula ?? 0),
            (int) $alerta->id,
            (int) ($alerta->ref_cod_aluno ?? 0),
        );

        return view('busca-ativa::alerta-detalhe', [
            'alerta' => $alerta,
            'processoId' => $processoId ? (int) $processoId : null,
            'espelho' => $espelho,
        ]);
    }

    public function resolverAlerta(int $id): RedirectResponse
    {
        abort_unless(Acesso::pode(Processos::ALERTAS, 'cadastra'), 403);
        DB::table('busca_ativa.alerta')->where('id', $id)->update([
            'status' => 'resolvido',
            'resolvido_em' => now(),
            'updated_at' => now(),
        ]);

        if (class_exists(\iEducar\Packages\BuritiLagunaAmbiente\Support\Notificador::class)) {
            \iEducar\Packages\BuritiLagunaAmbiente\Support\Notificador::paraProcesso(
                Processos::PROCESSOS,
                'Busca ativa: alerta #'.$id.' resolvido.',
                '/busca-ativa/alertas/'.$id,
            );
        }

        return back()->with('ok', 'Alerta resolvido.');
    }

    public function processos(Request $request): View
    {
        abort_unless(Acesso::pode(Processos::PROCESSOS), 403);
        $etapa = trim((string) $request->query('etapa', ''));
        $status = trim((string) $request->query('status', ''));

        $q = Schema::hasTable('busca_ativa.processo')
            ? DB::table('busca_ativa.processo as pr')
                ->leftJoin('pmieducar.aluno as al', 'al.cod_aluno', '=', 'pr.ref_cod_aluno')
                ->leftJoin('cadastro.pessoa as p', 'p.idpes', '=', 'al.ref_idpes')
                ->leftJoin('pmieducar.escola as e', 'e.cod_escola', '=', 'pr.ref_cod_escola')
                ->leftJoin('cadastro.juridica as j', 'j.idpes', '=', 'e.ref_idpes')
                ->orderByDesc('pr.aberto_em')
                ->limit(200)
            : null;

        if ($q !== null) {
            if ($etapa !== '' && in_array($etapa, EtapasProcesso::todas(), true)) {
                $q->where('pr.etapa_atual', $etapa);
            }
            if ($status !== '' && in_array($status, ['aberto', 'encerrado'], true)) {
                $q->where('pr.status', $status);
            }
            $rows = $q->get(['pr.*', 'p.nome as aluno_nome', 'j.fantasia as escola_nome']);
        } else {
            $rows = collect();
        }

        return view('busca-ativa::processos', [
            'processos' => $rows,
            'etapas' => EtapasProcesso::todas(),
            'filtroEtapa' => $etapa,
            'filtroStatus' => $status,
        ]);
    }

    public function processoDetalhe(int $id): View
    {
        abort_unless(Acesso::pode(Processos::PROCESSOS), 403);
        $processo = DB::table('busca_ativa.processo as pr')
            ->leftJoin('pmieducar.aluno as al', 'al.cod_aluno', '=', 'pr.ref_cod_aluno')
            ->leftJoin('cadastro.pessoa as p', 'p.idpes', '=', 'al.ref_idpes')
            ->leftJoin('pmieducar.escola as e', 'e.cod_escola', '=', 'pr.ref_cod_escola')
            ->leftJoin('cadastro.juridica as j', 'j.idpes', '=', 'e.ref_idpes')
            ->where('pr.id', $id)
            ->first(['pr.*', 'p.nome as aluno_nome', 'j.fantasia as escola_nome']);
        abort_if($processo === null, 404);

        $eventos = DB::table('busca_ativa.processo_evento')
            ->where('processo_id', $id)
            ->orderByDesc('created_at')
            ->get();
        $familia = Schema::hasTable('busca_ativa.familia_acompanhamento')
            ? DB::table('busca_ativa.familia_acompanhamento')->where('processo_id', $id)->orderByDesc('created_at')->get()
            : collect();

        $espelho = \iEducar\Packages\BuritiBuscaAtiva\Services\EspelhoChamadaService::paraMatricula(
            (int) ($processo->ref_cod_matricula ?? 0),
            isset($processo->alerta_id) ? (int) $processo->alerta_id : null,
            (int) ($processo->ref_cod_aluno ?? 0),
        );

        return view('busca-ativa::processo-detalhe', compact('processo', 'eventos', 'familia', 'espelho'));
    }

    public function atualizarProcesso(Request $request, int $id): RedirectResponse
    {
        abort_unless(Acesso::pode(Processos::PROCESSOS, 'cadastra'), 403);
        $acao = $request->input('acao');
        if ($acao === 'etapa') {
            $etapa = (string) $request->input('etapa');
            if (! in_array($etapa, EtapasProcesso::todas(), true)) {
                return back()->with('erro', 'Etapa inválida.');
            }
            ProcessoService::atualizarEtapa($id, $etapa, $request->input('observacoes'));
        } elseif ($acao === 'encerrar') {
            ProcessoService::encerrar($id, (string) $request->input('resultado', 'retorno'), $request->input('observacoes'));
        } else {
            ProcessoService::registrarEvento($id, 'anotacao', $request->input('observacoes'), $request->input('canal'));
        }

        return back()->with('ok', 'Processo atualizado.');
    }

    public function configuracao(): RedirectResponse
    {
        abort_unless(Acesso::pode(Processos::CONFIGURACAO), 403);

        return redirect('/configuracoes/modulos?modulo=busca-ativa')
            ->with('ok', 'Flags e limites da Busca Ativa ficam em Configurações → Módulos.');
    }

    public function salvarConfiguracao(Request $request): RedirectResponse
    {
        abort_unless(Acesso::pode(Processos::CONFIGURACAO, 'cadastra'), 403);

        return redirect('/configuracoes/modulos?modulo=busca-ativa')
            ->with('ok', 'Use Configurações → Módulos para salvar as diretivas da Busca Ativa.');
    }

    public function relatorios(): View
    {
        abort_unless(Acesso::pode(Processos::RELATORIOS), 403);

        return view('busca-ativa::relatorios', RelatorioGestao::montar(120));
    }

    public function exportarRelatorios(Request $request): Response|RedirectResponse
    {
        abort_unless(Acesso::pode(Processos::RELATORIOS), 403);

        $item = strtolower(trim((string) $request->input('item', 'alunos')));
        $formato = strtolower(trim((string) $request->input('formato', $item === 'alunos' ? 'xlsx' : 'pdf')));

        if ($item === 'alunos') {
            $alunos = RelatorioGestao::listaAlunos(2000);

            return \iEducar\Packages\BuritiExportacaoUnificada\Support\ExportacaoModulo::download(
                'Lista de alunos — Busca Ativa',
                RelatorioGestao::cabecalhosExcelAlunos(),
                RelatorioGestao::linhasExcelAlunos($alunos),
                'Busca ativa',
                $formato !== '' ? $formato : 'xlsx',
                count($alunos).' registro(s) · gestão da frequência e permanência escolar',
                null,
                'busca-ativa-alunos',
            );
        }

        $dados = RelatorioGestao::montar(1);
        $linhas = [];
        foreach ($dados['porTipo'] as $r) {
            $linhas[] = ['Alertas por tipo', $r['chave'], $r['total']];
        }
        foreach ($dados['porSeveridade'] as $r) {
            $linhas[] = ['Alertas por severidade', $r['chave'], $r['total']];
        }
        foreach ($dados['porStatusAlerta'] as $r) {
            $linhas[] = ['Alertas por status', $r['chave'], $r['total']];
        }
        foreach ($dados['porStatusProcesso'] as $r) {
            $linhas[] = ['Processos por status', $r['chave'], $r['total']];
        }
        foreach ($dados['porEtapa'] as $r) {
            $linhas[] = ['Processos abertos por etapa', $r['rotulo'], $r['total']];
        }
        foreach ($dados['porEscola'] as $r) {
            $linhas[] = ['Alertas abertos por escola', $r['chave'], $r['total']];
        }
        foreach ($dados['qualidade'] as $q) {
            $linhas[] = ['Indicador de qualidade', $q['rotulo'], $q['valor']];
        }

        return \iEducar\Packages\BuritiExportacaoUnificada\Support\ExportacaoModulo::download(
            'Relatórios — Busca Ativa',
            ['Dimensão', 'Chave', 'Total'],
            $linhas,
            'Busca ativa',
            $formato !== '' ? $formato : 'pdf',
            'Síntese gerencial de alertas, processos e qualidade',
            null,
            'busca-ativa-relatorios',
        );
    }
}

