<?php

namespace iEducar\Packages\BuritiBuscaAtiva\Console;

use iEducar\Packages\BuritiBuscaAtiva\Services\BuscaAtivaDemoService;
use Illuminate\Console\Command;

class BuscaAtivaDemoCommand extends Command
{
    protected $signature = 'busca-ativa:demo
                            {--force : Recria cenários demo mesmo se já existirem}
                            {--faltas=24 : Quantidade de matrículas com faltas semeadas}';

    protected $description = 'PoC — povoa Busca Ativa com faltas, alertas e processos multi-etapa/atores';

    public function handle(BuscaAtivaDemoService $demo): int
    {
        $force = (bool) $this->option('force');
        $faltas = max(5, min(80, (int) $this->option('faltas')));

        $this->info('Busca Ativa · carga demo multi-etapa');
        $r = $demo->executar($force, $faltas);

        if ($r['pulado']) {
            $this->warn('  '.$r['mensagem']);

            return self::SUCCESS;
        }

        if ($r['mensagem'] !== '' && str_contains($r['mensagem'], 'ausente')) {
            $this->error('  '.$r['mensagem']);

            return self::FAILURE;
        }

        $this->line('  '.$r['mensagem']);
        $this->line(sprintf(
            '  detector: analisados=%d alertas=%d processos=%d',
            $r['detector']['analisados'],
            $r['detector']['alertas'],
            $r['detector']['processos'],
        ));

        return self::SUCCESS;
    }
}
