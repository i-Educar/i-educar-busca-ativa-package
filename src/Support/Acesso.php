<?php

namespace iEducar\Packages\BuritiBuscaAtiva\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class Acesso
{
    public static function pode(int $processo, string $acao = 'visualiza'): bool
    {
        $user = Auth::user();
        if ($user === null) {
            return false;
        }

        $tipo = (int) ($user->ref_cod_tipo_usuario ?? 0);
        if ($tipo <= 0) {
            return true;
        }

        if (! Schema::hasTable('menus') || ! Schema::hasTable('pmieducar.menu_tipo_usuario')) {
            return true;
        }

        $menuId = DB::table('menus')->where('process', $processo)->value('id');
        if ($menuId === null) {
            return true;
        }

        $row = DB::table('pmieducar.menu_tipo_usuario')
            ->where('ref_cod_tipo_usuario', $tipo)
            ->where('menu_id', $menuId)
            ->first();

        if ($row === null) {
            return false;
        }

        return match ($acao) {
            'cadastra' => (int) ($row->cadastra ?? 0) === 1,
            'exclui' => (int) ($row->exclui ?? 0) === 1,
            default => (int) ($row->visualiza ?? 0) === 1,
        };
    }
}
