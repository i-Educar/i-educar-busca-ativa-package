<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('pmieducar.curso', 'busca_ativa')) {
            DB::statement('ALTER TABLE pmieducar.curso ADD COLUMN busca_ativa BOOLEAN NOT NULL DEFAULT FALSE');
            DB::statement("COMMENT ON COLUMN pmieducar.curso.busca_ativa IS 'Quando verdadeiro, turmas e matrículas deste curso entram no fluxo de Busca Ativa'");
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('pmieducar.curso', 'busca_ativa')) {
            DB::statement('ALTER TABLE pmieducar.curso DROP COLUMN busca_ativa');
        }
    }
};
