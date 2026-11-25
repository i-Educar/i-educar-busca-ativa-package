<?php

namespace iEducar\Packages\BuritiBuscaAtiva\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class ProcessoService
{
    /**
     * @param  array<string, mixed>  $alerta
     */
    public static function abrirDeAlerta(array $alerta, ?array $cfg = null): ?int
    {
        if (! Schema::hasTable('busca_ativa.processo')) {
            return null;
        }

        $aberto = DB::table('busca_ativa.processo')
            ->where('ref_cod_matricula', $alerta['ref_cod_matricula'])
            ->where('status', 'aberto')
            ->value('id');
        if ($aberto) {
            return (int) $aberto;
        }

        $cfg ??= ConfiguracaoService::obter((int) ($alerta['ref_cod_instituicao'] ?? null));
        $dias = (int) ($cfg['dias_sem_contato_escalar'] ?? 7);

        $id = (int) DB::table('busca_ativa.processo')->insertGetId([
            'alerta_id' => $alerta['id'] ?? null,
            'ref_cod_instituicao' => $alerta['ref_cod_instituicao'],
            'ref_cod_escola' => $alerta['ref_cod_escola'] ?? null,
            'ref_cod_matricula' => $alerta['ref_cod_matricula'],
            'ref_cod_aluno' => $alerta['ref_cod_aluno'],
            'status' => 'aberto',
            'etapa_atual' => 'contato_inicial',
            'aberto_em' => now(),
            'prazo_contato_em' => now()->addDays($dias),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        self::registrarEvento($id, 'abertura', 'Processo aberto a partir do alerta #'.($alerta['id'] ?? '—'));

        return $id;
    }

    public static function registrarEvento(int $processoId, string $tipo, ?string $descricao = null, ?string $canal = null, array $payload = []): void
    {
        if (! Schema::hasTable('busca_ativa.processo_evento')) {
            return;
        }

        DB::table('busca_ativa.processo_evento')->insert([
            'processo_id' => $processoId,
            'tipo' => $tipo,
            'descricao' => $descricao,
            'canal' => $canal,
            'usuario_id' => Auth::id(),
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
        ]);
    }

    public static function atualizarEtapa(int $processoId, string $etapa, ?string $observacao = null): void
    {
        $proc = DB::table('busca_ativa.processo')->where('id', $processoId)->first();
        DB::table('busca_ativa.processo')->where('id', $processoId)->update([
            'etapa_atual' => $etapa,
            'observacoes' => $observacao,
            'updated_at' => now(),
        ]);
        self::registrarEvento($processoId, 'etapa', 'Etapa: '.$etapa, null, ['etapa' => $etapa]);

        if ($proc && class_exists(\iEducar\Packages\BuritiLagunaAmbiente\Support\Notificador::class)) {
            \iEducar\Packages\BuritiLagunaAmbiente\Support\Notificador::paraProcesso(
                \iEducar\Packages\BuritiBuscaAtiva\Support\Processos::PROCESSOS,
                "Busca Ativa: processo #{$processoId} avançou para a etapa «{$etapa}».",
                '/busca-ativa/processos/'.$processoId,
                $proc->ref_cod_escola ? (int) $proc->ref_cod_escola : null,
                \App\Models\NotificationType::ACTIVE_LOOKING,
            );
        }
    }

    public static function encerrar(int $processoId, string $resultado, ?string $observacao = null): void
    {
        $proc = DB::table('busca_ativa.processo')->where('id', $processoId)->first();
        DB::table('busca_ativa.processo')->where('id', $processoId)->update([
            'status' => 'encerrado',
            'resultado' => $resultado,
            'observacoes' => $observacao,
            'encerrado_em' => now(),
            'updated_at' => now(),
        ]);
        self::registrarEvento($processoId, 'encerramento', 'Resultado: '.$resultado);

        if ($proc && class_exists(\iEducar\Packages\BuritiLagunaAmbiente\Support\Notificador::class)) {
            \iEducar\Packages\BuritiLagunaAmbiente\Support\Notificador::paraProcesso(
                \iEducar\Packages\BuritiBuscaAtiva\Support\Processos::PROCESSOS,
                "Busca Ativa: processo #{$processoId} encerrado — resultado «{$resultado}».",
                '/busca-ativa/processos/'.$processoId,
                $proc->ref_cod_escola ? (int) $proc->ref_cod_escola : null,
                \App\Models\NotificationType::ACTIVE_LOOKING,
            );
        }
    }
}
