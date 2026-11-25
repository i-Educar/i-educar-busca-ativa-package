<?php

namespace iEducar\Packages\BuritiBuscaAtiva\Services;

use iEducar\Packages\BuritiBuscaAtiva\Support\EtapasProcesso;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Carga demonstrativa: faltas → alertas → processos multi-etapa com atores/regras distintas.
 */
final class BuscaAtivaDemoService
{
    public const DEMO_TAG = 'laguna-carga-v1';

    /**
     * @return array{
     *   cursos:int,
     *   faltas:int,
     *   alertas:int,
     *   processos:int,
     *   eventos:int,
     *   familia:int,
     *   detector:array{analisados:int,alertas:int,processos:int},
     *   pulado:bool,
     *   mensagem:string
     * }
     */
    public function executar(bool $force = false, int $matriculasFalta = 24): array
    {
        $vazio = [
            'cursos' => 0,
            'faltas' => 0,
            'alertas' => 0,
            'processos' => 0,
            'eventos' => 0,
            'familia' => 0,
            'detector' => ['analisados' => 0, 'alertas' => 0, 'processos' => 0],
            'pulado' => false,
            'mensagem' => '',
        ];

        if (! Schema::hasTable('busca_ativa.processo')) {
            $vazio['mensagem'] = 'schema busca_ativa ausente — rode migrate';

            return $vazio;
        }

        if (! $force && $this->jaPovoado()) {
            $vazio['pulado'] = true;
            $vazio['mensagem'] = 'já povoado (use --force para recriar cenários)';

            return $vazio;
        }

        if ($force) {
            $this->limparDemo();
        }

        $instituicao = ConfiguracaoService::instituicaoPadrao();
        $cursos = $this->marcarCursos();
        $this->salvarRegras($instituicao);
        $faltas = $this->semearFaltas($matriculasFalta);
        $detector = DetectorInfrequencia::executar($instituicao);
        $cenario = $this->semearProcessosMultiEtapa($instituicao);

        return [
            'cursos' => $cursos,
            'faltas' => $faltas,
            'alertas' => $cenario['alertas'] + (int) ($detector['alertas'] ?? 0),
            'processos' => $cenario['processos'] + (int) ($detector['processos'] ?? 0),
            'eventos' => $cenario['eventos'],
            'familia' => $cenario['familia'],
            'detector' => $detector,
            'pulado' => false,
            'mensagem' => sprintf(
                'cursos=%d faltas=%d alertas≈%d processos≈%d eventos=%d familia=%d · detector analisou %d',
                $cursos,
                $faltas,
                $cenario['alertas'] + (int) ($detector['alertas'] ?? 0),
                $cenario['processos'] + (int) ($detector['processos'] ?? 0),
                $cenario['eventos'],
                $cenario['familia'],
                (int) ($detector['analisados'] ?? 0),
            ),
        ];
    }

    private function jaPovoado(): bool
    {
        return DB::table('busca_ativa.processo')
            ->whereRaw("metadados->>'demo' = ?", [self::DEMO_TAG])
            ->exists();
    }

    private function limparDemo(): void
    {
        $ids = DB::table('busca_ativa.processo')
            ->whereRaw("metadados->>'demo' = ?", [self::DEMO_TAG])
            ->pluck('id');
        if ($ids->isNotEmpty()) {
            if (Schema::hasTable('busca_ativa.familia_acompanhamento')) {
                DB::table('busca_ativa.familia_acompanhamento')->whereIn('processo_id', $ids)->delete();
            }
            if (Schema::hasTable('busca_ativa.processo_evento')) {
                DB::table('busca_ativa.processo_evento')->whereIn('processo_id', $ids)->delete();
            }
            DB::table('busca_ativa.processo')->whereIn('id', $ids)->delete();
        }
        DB::table('busca_ativa.alerta')
            ->whereRaw("metadados->>'demo' = ?", [self::DEMO_TAG])
            ->delete();
    }

    private function marcarCursos(): int
    {
        if (! Schema::hasColumn('pmieducar.curso', 'busca_ativa')) {
            return 0;
        }

        return (int) DB::table('pmieducar.curso')->where('ativo', 1)->update(['busca_ativa' => true]);
    }

    private function salvarRegras(int $instituicao): void
    {
        ConfiguracaoService::salvar([
            'faltas_consecutivas_alerta' => 3,
            'faltas_mensais_alerta' => 5,
            'frequencia_minima_percentual' => 75,
            'dias_sem_contato_escalar' => 7,
            'gerar_alerta_atendimento' => true,
            'notificar_familia' => true,
            'somente_cursos_marcados' => true,
            'abrir_processo_automatico' => true,
            'ativo' => true,
            'regras_json' => [
                'demo' => self::DEMO_TAG,
                'escalonamento' => [
                    ['dias' => 0, 'ator' => 'escola', 'acao' => 'contato_inicial'],
                    ['dias' => 3, 'ator' => 'coordenacao', 'acao' => 'visita'],
                    ['dias' => 7, 'ator' => 'sme_busca_ativa', 'acao' => 'encaminhamento'],
                    ['dias' => 14, 'ator' => 'assistencia', 'acao' => 'acompanhamento'],
                ],
                'permissoes_etapa' => [
                    EtapasProcesso::CONTATO_INICIAL => ['diretor', 'coordenador', 'atendente'],
                    EtapasProcesso::VISITA => ['coordenador', 'busca_ativa'],
                    EtapasProcesso::ENCAMINHAMENTO => ['busca_ativa', 'secretaria_sme'],
                    EtapasProcesso::ACOMPANHAMENTO => ['busca_ativa', 'assistencia', 'familia_api'],
                    EtapasProcesso::RETORNO => ['diretor', 'coordenador', 'busca_ativa'],
                ],
            ],
        ], $instituicao);
    }

    private function semearFaltas(int $limite): int
    {
        if (! Schema::hasTable('modules.falta_aluno') || ! Schema::hasTable('modules.falta_geral')) {
            return 0;
        }

        $ano = (int) date('Y');
        $mats = DB::table('pmieducar.matricula as m')
            ->join('pmieducar.matricula_turma as mt', function ($j): void {
                $j->on('mt.ref_cod_matricula', '=', 'm.cod_matricula')->where('mt.ativo', 1);
            })
            ->where('m.ativo', 1)
            ->where('m.ano', $ano)
            ->orderBy('m.cod_matricula')
            ->limit(max(5, $limite))
            ->pluck('m.cod_matricula');

        $n = 0;
        $i = 0;
        foreach ($mats as $matriculaId) {
            $matriculaId = (int) $matriculaId;
            $faId = DB::table('modules.falta_aluno')->where('matricula_id', $matriculaId)->value('id');
            if ($faId === null) {
                $faId = DB::table('modules.falta_aluno')->insertGetId([
                    'matricula_id' => $matriculaId,
                    'tipo_falta' => 1, // geral
                ]);
            }
            // Cenários: 3, 5, 8, 12 faltas em etapas diferentes
            $qtd = [3, 4, 5, 6, 8, 10, 12][$i % 7];
            $etapa = (string) (($i % 4) + 1);
            DB::table('modules.falta_geral')->updateOrInsert(
                ['falta_aluno_id' => $faId, 'etapa' => $etapa],
                ['quantidade' => $qtd]
            );
            $n++;
            $i++;
        }

        return $n;
    }

    /**
     * @return array{alertas:int,processos:int,eventos:int,familia:int}
     */
    private function semearProcessosMultiEtapa(int $instituicao): array
    {
        $atores = $this->mapearAtores();
        $mats = $this->amostraMatriculas(12);
        if ($mats === []) {
            return ['alertas' => 0, 'processos' => 0, 'eventos' => 0, 'familia' => 0];
        }

        $cenarios = [
            [
                'etapa' => EtapasProcesso::CONTATO_INICIAL,
                'status' => 'aberto',
                'tipo' => 'faltas_consecutivas',
                'sev' => 'media',
                'resultado' => null,
                'eventos' => [
                    ['tipo' => 'abertura', 'desc' => 'Alerta gerado — contato inicial pela escola', 'canal' => 'sistema', 'ator' => 'diretor'],
                    ['tipo' => 'anotacao', 'desc' => 'Tentativa de contato telefônico sem sucesso', 'canal' => 'telefone', 'ator' => 'atendente'],
                ],
            ],
            [
                'etapa' => EtapasProcesso::VISITA,
                'status' => 'aberto',
                'tipo' => 'faltas_mensais',
                'sev' => 'alta',
                'resultado' => null,
                'eventos' => [
                    ['tipo' => 'abertura', 'desc' => 'Processo aberto', 'canal' => 'sistema', 'ator' => 'coordenador'],
                    ['tipo' => 'etapa', 'desc' => 'Etapa: visita', 'canal' => null, 'ator' => 'coordenador'],
                    ['tipo' => 'anotacao', 'desc' => 'Visita agendada para o responsável', 'canal' => 'visita', 'ator' => 'busca_ativa'],
                ],
            ],
            [
                'etapa' => EtapasProcesso::ENCAMINHAMENTO,
                'status' => 'aberto',
                'tipo' => 'frequencia_baixa',
                'sev' => 'alta',
                'resultado' => null,
                'eventos' => [
                    ['tipo' => 'abertura', 'desc' => 'Processo aberto', 'canal' => 'sistema', 'ator' => 'coordenador'],
                    ['tipo' => 'etapa', 'desc' => 'Etapa: encaminhamento', 'canal' => null, 'ator' => 'busca_ativa'],
                    ['tipo' => 'anotacao', 'desc' => 'Encaminhado à SME — prazo de contato vencido', 'canal' => 'interno', 'ator' => 'secretaria_sme'],
                ],
            ],
            [
                'etapa' => EtapasProcesso::ACOMPANHAMENTO,
                'status' => 'aberto',
                'tipo' => 'faltas_consecutivas',
                'sev' => 'media',
                'resultado' => null,
                'familia' => true,
                'eventos' => [
                    ['tipo' => 'abertura', 'desc' => 'Processo aberto', 'canal' => 'sistema', 'ator' => 'diretor'],
                    ['tipo' => 'etapa', 'desc' => 'Etapa: acompanhamento', 'canal' => null, 'ator' => 'assistencia'],
                    ['tipo' => 'anotacao', 'desc' => 'Família comprometeu-se a retornar em 5 dias', 'canal' => 'api', 'ator' => 'familia_api'],
                ],
            ],
            [
                'etapa' => EtapasProcesso::RETORNO,
                'status' => 'aberto',
                'tipo' => 'faltas_mensais',
                'sev' => 'baixa',
                'resultado' => null,
                'eventos' => [
                    ['tipo' => 'abertura', 'desc' => 'Processo aberto', 'canal' => 'sistema', 'ator' => 'coordenador'],
                    ['tipo' => 'etapa', 'desc' => 'Etapa: retorno', 'canal' => null, 'ator' => 'busca_ativa'],
                    ['tipo' => 'anotacao', 'desc' => 'Aluno compareceu — aguardando estabilização de frequência', 'canal' => 'escola', 'ator' => 'diretor'],
                ],
            ],
            [
                'etapa' => EtapasProcesso::RETORNO,
                'status' => 'encerrado',
                'tipo' => 'faltas_consecutivas',
                'sev' => 'media',
                'resultado' => 'retorno',
                'eventos' => [
                    ['tipo' => 'abertura', 'desc' => 'Processo aberto', 'canal' => 'sistema', 'ator' => 'diretor'],
                    ['tipo' => 'etapa', 'desc' => 'Etapa: retorno', 'canal' => null, 'ator' => 'coordenador'],
                    ['tipo' => 'encerramento', 'desc' => 'Resultado: retorno', 'canal' => null, 'ator' => 'busca_ativa'],
                ],
            ],
            [
                'etapa' => EtapasProcesso::ENCAMINHAMENTO,
                'status' => 'encerrado',
                'tipo' => 'frequencia_baixa',
                'sev' => 'alta',
                'resultado' => 'abandono',
                'eventos' => [
                    ['tipo' => 'abertura', 'desc' => 'Processo aberto', 'canal' => 'sistema', 'ator' => 'coordenador'],
                    ['tipo' => 'etapa', 'desc' => 'Etapa: encaminhamento', 'canal' => null, 'ator' => 'busca_ativa'],
                    ['tipo' => 'anotacao', 'desc' => 'Conselho tutelar notificado', 'canal' => 'oficial', 'ator' => 'assistencia'],
                    ['tipo' => 'encerramento', 'desc' => 'Resultado: abandono', 'canal' => null, 'ator' => 'secretaria_sme'],
                ],
            ],
            [
                'etapa' => EtapasProcesso::VISITA,
                'status' => 'encerrado',
                'tipo' => 'faltas_mensais',
                'sev' => 'media',
                'resultado' => 'transferencia',
                'eventos' => [
                    ['tipo' => 'abertura', 'desc' => 'Processo aberto', 'canal' => 'sistema', 'ator' => 'diretor'],
                    ['tipo' => 'etapa', 'desc' => 'Etapa: visita', 'canal' => null, 'ator' => 'coordenador'],
                    ['tipo' => 'encerramento', 'desc' => 'Resultado: transferencia', 'canal' => null, 'ator' => 'secretaria_sme'],
                ],
            ],
        ];

        $alertas = 0;
        $processos = 0;
        $eventos = 0;
        $familia = 0;

        foreach ($cenarios as $idx => $cenario) {
            $mat = $mats[$idx % count($mats)];
            $alertaId = (int) DB::table('busca_ativa.alerta')->insertGetId([
                'ref_cod_instituicao' => $instituicao,
                'ref_cod_escola' => $mat['escola'],
                'ref_cod_curso' => $mat['curso'],
                'ref_cod_turma' => $mat['turma'],
                'ref_cod_matricula' => $mat['matricula'],
                'ref_cod_aluno' => $mat['aluno'],
                'tipo' => $cenario['tipo'],
                'severidade' => $cenario['sev'],
                'status' => $cenario['status'] === 'encerrado' ? 'resolvido' : 'aberto',
                'faltas_consecutivas' => 4 + $idx,
                'faltas_periodo' => 6 + $idx,
                'frequencia_percentual' => max(40, 90 - ($idx * 5)),
                'motivo' => 'Cenário demo · '.$cenario['etapa'].' · atores/regras Laguna',
                'metadados' => json_encode(['demo' => self::DEMO_TAG, 'cenario' => $idx], JSON_UNESCAPED_UNICODE),
                'detectado_em' => now()->subDays(10 - min(9, $idx)),
                'resolvido_em' => $cenario['status'] === 'encerrado' ? now()->subDays(1) : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $alertas++;

            $resp = $atores['busca_ativa']['id'] ?? ($atores['coordenador']['id'] ?? null);
            $processoId = (int) DB::table('busca_ativa.processo')->insertGetId([
                'alerta_id' => $alertaId,
                'ref_cod_instituicao' => $instituicao,
                'ref_cod_escola' => $mat['escola'],
                'ref_cod_matricula' => $mat['matricula'],
                'ref_cod_aluno' => $mat['aluno'],
                'status' => $cenario['status'],
                'etapa_atual' => $cenario['etapa'],
                'responsavel_id' => $resp,
                'aberto_em' => now()->subDays(12 - min(11, $idx)),
                'prazo_contato_em' => now()->addDays($idx % 2 === 0 ? -2 : 5),
                'encerrado_em' => $cenario['status'] === 'encerrado' ? now()->subDay() : null,
                'resultado' => $cenario['resultado'],
                'observacoes' => 'Demo multi-etapa · '.EtapasProcesso::rotulo($cenario['etapa']),
                'metadados' => json_encode([
                    'demo' => self::DEMO_TAG,
                    'cenario' => $idx,
                    'regra' => $cenario['etapa'],
                ], JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $processos++;

            if ($cenario['status'] !== 'encerrado' && ($cfgBridge = true)) {
                AtendimentoBridge::publicarAlerta([
                    'id' => $alertaId,
                    'ref_cod_aluno' => $mat['aluno'],
                    'ref_cod_matricula' => $mat['matricula'],
                    'ref_cod_escola' => $mat['escola'],
                    'tipo' => $cenario['tipo'],
                    'motivo' => 'Demo busca ativa · '.$cenario['etapa'],
                ]);
            }
            unset($cfgBridge);

            foreach ($cenario['eventos'] as $ev) {
                $uid = $atores[$ev['ator']]['id'] ?? null;
                DB::table('busca_ativa.processo_evento')->insert([
                    'processo_id' => $processoId,
                    'tipo' => $ev['tipo'],
                    'descricao' => $ev['desc'].' · ator='.($atores[$ev['ator']]['nome'] ?? $ev['ator']),
                    'canal' => $ev['canal'],
                    'usuario_id' => $uid,
                    'payload' => json_encode([
                        'demo' => self::DEMO_TAG,
                        'ator_chave' => $ev['ator'],
                        'ator_nome' => $atores[$ev['ator']]['nome'] ?? $ev['ator'],
                    ], JSON_UNESCAPED_UNICODE),
                    'created_at' => now()->subHours(max(1, 20 - $eventos)),
                ]);
                $eventos++;
            }

            if (! empty($cenario['familia']) && Schema::hasTable('busca_ativa.familia_acompanhamento')) {
                DB::table('busca_ativa.familia_acompanhamento')->insert([
                    'processo_id' => $processoId,
                    'ref_cod_aluno' => $mat['aluno'],
                    'canal' => 'api',
                    'status_contato' => 'compromisso',
                    'retorno_familia' => 'Responsável confirma retorno do aluno em até 5 dias úteis.',
                    'compromisso_retorno_em' => now()->addDays(5)->toDateString(),
                    'registrado_por' => 'App Família (demo)',
                    'payload' => json_encode(['demo' => self::DEMO_TAG], JSON_UNESCAPED_UNICODE),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $familia++;
            }
        }

        return compact('alertas', 'processos', 'eventos', 'familia');
    }

    /**
     * @return list<array{matricula:int,aluno:int,escola:?int,curso:?int,turma:?int}>
     */
    private function amostraMatriculas(int $limite): array
    {
        $ano = (int) date('Y');
        $rows = DB::table('pmieducar.matricula as m')
            ->leftJoin('pmieducar.matricula_turma as mt', function ($j): void {
                $j->on('mt.ref_cod_matricula', '=', 'm.cod_matricula')->where('mt.ativo', 1);
            })
            ->where('m.ativo', 1)
            ->where('m.ano', $ano)
            ->orderBy('m.cod_matricula')
            ->limit($limite)
            ->get([
                'm.cod_matricula',
                'm.ref_cod_aluno',
                'm.ref_ref_cod_escola as escola',
                'm.ref_cod_curso as curso',
                'mt.ref_cod_turma as turma',
            ]);

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'matricula' => (int) $r->cod_matricula,
                'aluno' => (int) $r->ref_cod_aluno,
                'escola' => $r->escola !== null ? (int) $r->escola : null,
                'curso' => $r->curso !== null ? (int) $r->curso : null,
                'turma' => $r->turma !== null ? (int) $r->turma : null,
            ];
        }

        return $out;
    }

    /**
     * @return array<string, array{id:?int,nome:string}>
     */
    private function mapearAtores(): array
    {
        $defs = [
            'diretor' => '42091020003',
            'coordenador' => '42091020004',
            'atendente' => '42091020009',
            'secretaria_sme' => '42091020002',
            'busca_ativa' => '42091020014',
            'assistencia' => '42091020015',
            'familia_api' => null,
        ];
        $out = [];
        foreach ($defs as $chave => $cpf) {
            $id = null;
            $nome = $chave;
            if ($cpf !== null && Schema::hasTable('portal.funcionario')) {
                $row = DB::table('portal.funcionario as f')
                    ->leftJoin('cadastro.pessoa as p', 'p.idpes', '=', 'f.ref_cod_pessoa_fj')
                    ->where('f.matricula', $cpf)
                    ->first(['f.ref_cod_pessoa_fj as id', 'p.nome']);
                if ($row) {
                    $id = (int) $row->id;
                    $nome = (string) ($row->nome ?: $chave);
                }
            }
            $out[$chave] = ['id' => $id, 'nome' => $nome === 'familia_api' ? 'App Família' : $nome];
        }

        return $out;
    }
}
