<?php

namespace iEducar\Packages\BuritiBuscaAtiva\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class AtendimentoBridge
{
    /**
     * Publica alerta na fila PoC do módulo de atendimento ao estudante.
     *
     * @param  array<string, mixed>  $alerta
     */
    public static function publicarAlerta(array $alerta): ?int
    {
        if (! Schema::hasTable('atendimento.poc_registro')) {
            return null;
        }

        $tipo = (string) config('busca-ativa.atendimento.tipo_poc', 'alerta_busca_ativa');
        $prioridade = (int) config('busca-ativa.atendimento.prioridade_padrao', 3);

        $alunoNome = null;
        if (! empty($alerta['ref_cod_aluno']) && Schema::hasTable('pmieducar.aluno')) {
            $alunoNome = DB::table('pmieducar.aluno as al')
                ->leftJoin('cadastro.pessoa as p', 'p.idpes', '=', 'al.ref_idpes')
                ->where('al.cod_aluno', $alerta['ref_cod_aluno'])
                ->value('p.nome');
        }

        $payload = [
            'origem' => 'busca_ativa',
            'alerta_id' => $alerta['id'] ?? null,
            'aluno' => $alunoNome ?: ('Aluno #'.($alerta['ref_cod_aluno'] ?? '?')),
            'aluno_id' => $alerta['ref_cod_aluno'] ?? null,
            'matricula_id' => $alerta['ref_cod_matricula'] ?? null,
            'escola_id' => $alerta['ref_cod_escola'] ?? null,
            'tipo_alerta' => $alerta['tipo'] ?? 'infrequencia',
            'motivo' => $alerta['motivo'] ?? 'Infrequência detectada pela Busca Ativa',
            'prioridade' => $prioridade,
            'status' => 'fila',
            'criado_em' => now()->toDateTimeString(),
        ];

        return (int) DB::table('atendimento.poc_registro')->insertGetId([
            'tipo' => $tipo,
            'titulo' => 'Busca ativa · '.($alunoNome ?: ('aluno #'.($alerta['ref_cod_aluno'] ?? '?'))),
            'meta' => $alerta['tipo'] ?? 'infrequencia',
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
        ]);
    }
}
