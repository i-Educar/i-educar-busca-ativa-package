<?php

namespace iEducar\Packages\BuritiBuscaAtiva\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class ApiTokenService
{
    /**
     * @return array{id:int,token:string,prefix:string}|null
     */
    public static function criar(string $nome, ?int $instituicao = null, ?int $ttlHoras = null): ?array
    {
        if (! Schema::hasTable('busca_ativa.api_token')) {
            return null;
        }

        $instituicao ??= ConfiguracaoService::instituicaoPadrao();
        $ttlHoras ??= (int) config('busca-ativa.api.token_ttl_horas', 72);
        $plain = 'ba_'.Str::random(40);
        $hash = hash('sha256', $plain);
        $prefix = substr($plain, 0, 10);

        $id = (int) DB::table('busca_ativa.api_token')->insertGetId([
            'ref_cod_instituicao' => $instituicao,
            'nome' => $nome,
            'token_hash' => $hash,
            'token_prefix' => $prefix,
            'escopos' => json_encode(['familia.read', 'familia.write'], JSON_UNESCAPED_UNICODE),
            'ativo' => true,
            'expira_em' => $ttlHoras > 0 ? now()->addHours($ttlHoras) : null,
            'created_by' => Auth::id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['id' => $id, 'token' => $plain, 'prefix' => $prefix];
    }

    public static function validar(?string $plain): ?object
    {
        if (! $plain || ! Schema::hasTable('busca_ativa.api_token')) {
            return null;
        }

        $hash = hash('sha256', $plain);
        $row = DB::table('busca_ativa.api_token')
            ->where('token_hash', $hash)
            ->where('ativo', true)
            ->first();

        if ($row === null) {
            return null;
        }

        if ($row->expira_em && now()->greaterThan($row->expira_em)) {
            return null;
        }

        DB::table('busca_ativa.api_token')->where('id', $row->id)->update([
            'ultimo_uso_em' => now(),
            'updated_at' => now(),
        ]);

        return $row;
    }

    public static function revogar(int $id): void
    {
        DB::table('busca_ativa.api_token')->where('id', $id)->update([
            'ativo' => false,
            'updated_at' => now(),
        ]);
    }
}
