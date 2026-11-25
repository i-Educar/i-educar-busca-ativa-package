<?php

namespace iEducar\Packages\BuritiBuscaAtiva\Http\Controllers;

use iEducar\Packages\BuritiBuscaAtiva\Services\ProcessoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FamiliaApiController extends Controller
{
    public function processos(Request $request): JsonResponse
    {
        $token = $request->attributes->get('busca_ativa_token');
        $q = DB::table('busca_ativa.processo as pr')
            ->leftJoin('pmieducar.aluno as al', 'al.cod_aluno', '=', 'pr.ref_cod_aluno')
            ->leftJoin('cadastro.pessoa as p', 'p.idpes', '=', 'al.ref_idpes')
            ->where('pr.ref_cod_instituicao', $token->ref_cod_instituicao)
            ->where('pr.status', 'aberto')
            ->orderByDesc('pr.aberto_em')
            ->limit(100)
            ->get(['pr.id', 'pr.ref_cod_aluno', 'pr.etapa_atual', 'pr.aberto_em', 'pr.prazo_contato_em', 'p.nome as aluno']);

        return response()->json(['data' => $q]);
    }

    public function processo(Request $request, int $id): JsonResponse
    {
        $token = $request->attributes->get('busca_ativa_token');
        $processo = DB::table('busca_ativa.processo')
            ->where('id', $id)
            ->where('ref_cod_instituicao', $token->ref_cod_instituicao)
            ->first();
        if ($processo === null) {
            return response()->json(['error' => 'Não encontrado'], 404);
        }

        $eventos = DB::table('busca_ativa.processo_evento')
            ->where('processo_id', $id)
            ->orderBy('created_at')
            ->get(['tipo', 'descricao', 'canal', 'created_at']);

        return response()->json(['data' => $processo, 'eventos' => $eventos]);
    }

    public function registrarAcompanhamento(Request $request, int $id): JsonResponse
    {
        $token = $request->attributes->get('busca_ativa_token');
        $processo = DB::table('busca_ativa.processo')
            ->where('id', $id)
            ->where('ref_cod_instituicao', $token->ref_cod_instituicao)
            ->first();
        if ($processo === null) {
            return response()->json(['error' => 'Não encontrado'], 404);
        }

        if (! Schema::hasTable('busca_ativa.familia_acompanhamento')) {
            return response()->json(['error' => 'Schema indisponível'], 503);
        }

        $aid = DB::table('busca_ativa.familia_acompanhamento')->insertGetId([
            'processo_id' => $id,
            'ref_cod_aluno' => $processo->ref_cod_aluno,
            'canal' => (string) $request->input('canal', 'api'),
            'status_contato' => (string) $request->input('status_contato', 'realizado'),
            'retorno_familia' => $request->input('retorno_familia'),
            'compromisso_retorno_em' => $request->input('compromisso_retorno_em'),
            'registrado_por' => $request->input('registrado_por', 'familia'),
            'payload' => json_encode($request->except(['token']), JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        ProcessoService::registrarEvento(
            $id,
            'familia',
            $request->input('retorno_familia', 'Acompanhamento familiar registrado via API'),
            (string) $request->input('canal', 'api'),
            ['familia_acompanhamento_id' => $aid]
        );

        if (class_exists(\iEducar\Packages\BuritiLagunaAmbiente\Support\Notificador::class)) {
            \iEducar\Packages\BuritiLagunaAmbiente\Support\Notificador::paraProcesso(
                \iEducar\Packages\BuritiBuscaAtiva\Support\Processos::PROCESSOS,
                'Busca Ativa: retorno familiar no processo #'.$id.' — '.($request->input('retorno_familia') ?: 'acompanhamento registrado').'.',
                '/busca-ativa/processos/'.$id,
                $processo->ref_cod_escola ? (int) $processo->ref_cod_escola : null,
                \App\Models\NotificationType::ACTIVE_LOOKING,
            );
        }

        if ($request->input('etapa')) {
            ProcessoService::atualizarEtapa($id, (string) $request->input('etapa'));
        }

        return response()->json(['ok' => true, 'id' => $aid], 201);
    }

    public function alertasAluno(Request $request, int $alunoId): JsonResponse
    {
        $token = $request->attributes->get('busca_ativa_token');
        $rows = DB::table('busca_ativa.alerta')
            ->where('ref_cod_aluno', $alunoId)
            ->where('ref_cod_instituicao', $token->ref_cod_instituicao)
            ->orderByDesc('detectado_em')
            ->limit(50)
            ->get(['id', 'tipo', 'severidade', 'status', 'motivo', 'faltas_consecutivas', 'frequencia_percentual', 'detectado_em']);

        return response()->json(['data' => $rows]);
    }
}
