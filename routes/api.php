<?php

/**
 * API familiar da Busca Ativa foi migrada para o Apps API (Sanctum):
 *   GET  /api/apps/v1/familia/busca-ativa/processos
 *   GET  /api/apps/v1/familia/busca-ativa/processos/{id}
 *   POST /api/apps/v1/familia/busca-ativa/processos/{id}/acompanhamento
 *   GET  /api/apps/v1/familia/filhos/{aluno}/busca-ativa/alertas
 *
 * Este arquivo permanece vazio de propósito (evita tokens paralelos).
 */

use Illuminate\Support\Facades\Route;

Route::prefix('busca-ativa/api/v1')->group(function (): void {
    Route::any('{any?}', static function () {
        return response()->json([
            'message' => 'API familiar da Busca Ativa está em /api/apps/v1/familia/busca-ativa/* (Sanctum, perfil familia).',
            'code' => 'moved_to_apps_api',
            'docs' => '/apps-api/funcoes',
        ], 410);
    })->where('any', '.*');
});
