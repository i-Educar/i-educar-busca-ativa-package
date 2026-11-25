<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tokens/API família migraram para Apps API (Sanctum).
 * Desativa o menu 882205 sem apagar o process ID (ACL legada).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('menus')) {
            return;
        }

        DB::table('menus')
            ->where('process', 882205)
            ->update([
                'active' => false,
                'title' => 'Tokens API (migrado → Apps da rede)',
                'link' => '/apps-api/governanca',
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('menus')) {
            return;
        }

        DB::table('menus')
            ->where('process', 882205)
            ->update([
                'active' => true,
                'title' => 'Tokens API',
                'link' => '/busca-ativa/api-tokens',
            ]);
    }
};
