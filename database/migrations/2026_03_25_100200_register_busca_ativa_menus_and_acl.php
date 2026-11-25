<?php

use App\Menu;
use App\Services\MenuCacheService;
use iEducar\Packages\BuritiBuscaAtiva\Support\Processos;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $escola = Menu::query()
            ->whereNull('parent_id')
            ->where(function ($q) {
                $q->where('process', 55)->orWhere('title', 'Escola');
            })
            ->first();

        if ($escola === null) {
            return;
        }

        $raiz = Menu::query()->updateOrCreate(['process' => Processos::RAIZ], [
            'parent_id' => $escola->getKey(),
            'title' => 'Busca Ativa',
            'description' => 'Alertas de falta, processos de reintegração e acompanhamento familiar',
            'link' => '/busca-ativa',
            'icon' => 'fa-search',
            'order' => 7,
            'type' => 2,
            'active' => true,
        ]);

        $links = [
            [Processos::PAINEL, 'Painel', '/busca-ativa', 'Visão geral', 1],
            [Processos::ALERTAS, 'Alertas', '/busca-ativa/alertas', 'Infrequência detectada', 2],
            [Processos::PROCESSOS, 'Processos', '/busca-ativa/processos', 'Busca ativa em andamento', 3],
            [Processos::CONFIGURACAO, 'Configuração', '/busca-ativa/configuracao', 'Regras e limites', 4],
            [Processos::API_TOKENS, 'Tokens API', '/busca-ativa/api-tokens', 'Acesso familiar/API', 5],
            [Processos::RELATORIOS, 'Relatórios', '/busca-ativa/relatorios', 'Risco de evasão', 6],
        ];

        foreach ($links as [$processo, $titulo, $link, $descricao, $ordem]) {
            Menu::query()->updateOrCreate(['process' => $processo], [
                'parent_id' => $raiz->getKey(),
                'title' => $titulo,
                'description' => $descricao,
                'link' => $link,
                'order' => $ordem,
                'type' => 3,
                'active' => true,
            ]);
        }

        $processos = Processos::todos();
        if (class_exists(\iEducar\Packages\BuritiLagunaAmbiente\Support\PermissaoMatriz::class)) {
            \iEducar\Packages\BuritiLagunaAmbiente\Support\PermissaoMatriz::conceder(
                $processos,
                null,
                [Processos::RELATORIOS],
            );
        } else {
            $tipos = DB::table('pmieducar.tipo_usuario')->where('ativo', 1)->get(['cod_tipo_usuario']);
            foreach ($tipos as $tipo) {
                foreach ($processos as $processo) {
                    $menuId = Menu::query()->where('process', $processo)->value('id');
                    if ($menuId === null) {
                        continue;
                    }
                    DB::table('pmieducar.menu_tipo_usuario')->updateOrInsert(
                        ['ref_cod_tipo_usuario' => $tipo->cod_tipo_usuario, 'menu_id' => $menuId],
                        ['cadastra' => 1, 'visualiza' => 1, 'exclui' => 0],
                    );
                }
            }
        }

        if (class_exists(MenuCacheService::class)) {
            app(MenuCacheService::class)->flushAll();
        }
    }

    public function down(): void
    {
        $raiz = Menu::query()->where('process', Processos::RAIZ)->first();
        if ($raiz === null) {
            return;
        }
        $folhas = Menu::query()->where('parent_id', $raiz->getKey())->pluck('id');
        $ids = $folhas->push($raiz->getKey());
        DB::table('pmieducar.menu_tipo_usuario')->whereIn('menu_id', $ids)->delete();
        Menu::query()->whereIn('id', $folhas)->delete();
        $raiz->delete();
        if (class_exists(MenuCacheService::class)) {
            app(MenuCacheService::class)->flushAll();
        }
    }
};
