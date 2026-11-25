<?php

use iEducar\Packages\BuritiBuscaAtiva\Http\Controllers\BuscaAtivaController;
use Illuminate\Support\Facades\Route;

Route::prefix('busca-ativa')->group(function (): void {
    Route::get('/', [BuscaAtivaController::class, 'index']);
    Route::post('/detectar', [BuscaAtivaController::class, 'detectar']);
    Route::get('/alertas', [BuscaAtivaController::class, 'alertas']);
    Route::get('/alertas/{id}', [BuscaAtivaController::class, 'alertaDetalhe'])->whereNumber('id');
    Route::post('/alertas/{id}/resolver', [BuscaAtivaController::class, 'resolverAlerta'])->whereNumber('id');
    Route::get('/processos', [BuscaAtivaController::class, 'processos']);
    Route::get('/processos/{id}', [BuscaAtivaController::class, 'processoDetalhe'])->whereNumber('id');
    Route::post('/processos/{id}', [BuscaAtivaController::class, 'atualizarProcesso'])->whereNumber('id');
    Route::get('/configuracao', [BuscaAtivaController::class, 'configuracao']);
    Route::post('/configuracao', [BuscaAtivaController::class, 'salvarConfiguracao']);
    Route::get('/relatorios', [BuscaAtivaController::class, 'relatorios']);
    Route::redirect('/api-tokens', '/apps-api/governanca');
    Route::get('/api-tokens-info', static fn () => view('busca-ativa::api-tokens'));
    // Tokens/API família: módulo Apps da rede (/apps-api) — Sanctum
});
