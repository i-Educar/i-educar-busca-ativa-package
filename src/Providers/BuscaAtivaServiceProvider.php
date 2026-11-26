<?php

namespace iEducar\Packages\BuritiBuscaAtiva\Providers;

use iEducar\Packages\BuritiBuscaAtiva\Console\BuscaAtivaDemoCommand;
use iEducar\Packages\BuritiBuscaAtiva\Console\DetectarInfrequenciaCommand;
use iEducar\Packages\BuritiBuscaAtiva\Support\Acesso;
use iEducar\Packages\BuritiBuscaAtiva\Support\Identidade;
use iEducar\Packages\BuritiBuscaAtiva\Support\MenuFluxo;
use iEducar\Packages\BuritiBuscaAtiva\Support\Processos;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class BuscaAtivaServiceProvider extends ServiceProvider
{
    public const string PACKAGE = 'buriti/i-educar-busca-ativa-package';

    public function register(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__, 2).'/config/busca-ativa.php', 'busca-ativa');
        // Fora de runningInConsole(): o check do Ambiente Laguna (HTTP) usa Artisan::all().
        $this->commands([
            DetectarInfrequenciaCommand::class,
            BuscaAtivaDemoCommand::class,
        ]);
    }

    public function boot(): void
    {
        if (class_exists(\iEducar\Packages\BuritiLagunaAmbiente\Support\BuritiUi::class)) {
            \iEducar\Packages\BuritiLagunaAmbiente\Support\BuritiUi::registrar();
        }

        $root = dirname(__DIR__, 2);
        $this->loadViewsFrom($root.'/resources/views', 'busca-ativa');

        if ($this->app->runningInConsole()) {
            if (\App\Support\Tenancy\PackageTenant::allows(self::PACKAGE)) {
                $this->loadMigrationsFrom($root.'/database/migrations');
            }
        }

        Route::middleware([
            'web',
            'ieducar.navigation',
            'ieducar.footer',
            'ieducar.xssbypass',
            'ieducar.suspended',
            'auth',
            'ieducar.checkresetpassword',
        ])->group($root.'/routes/web.php');

        // 410 Gone para o prefixo antigo — contrato novo em Apps API
        Route::middleware(['api'])->group($root.'/routes/api.php');

        View::composer('busca-ativa::*', function ($view): void {
            $view->with('identidade', Identidade::tokens());
            $view->with('menuGrupos', MenuFluxo::grupos([
                'painel' => Acesso::pode(Processos::PAINEL),
                'alertas' => Acesso::pode(Processos::ALERTAS),
                'processos' => Acesso::pode(Processos::PROCESSOS),
                'configuracao' => Acesso::pode(Processos::CONFIGURACAO),
                'relatorios' => Acesso::pode(Processos::RELATORIOS),
            ]));
        });
    }
}
