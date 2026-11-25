<?php

namespace iEducar\Packages\BuritiBuscaAtiva\Console;

use iEducar\Packages\BuritiBuscaAtiva\Services\DetectorInfrequencia;
use Illuminate\Console\Command;

class DetectarInfrequenciaCommand extends Command
{
    protected $signature = 'busca-ativa:detectar {--instituicao=} {--ano=}';

    protected $description = 'Detecta infrequência em cursos marcados para Busca Ativa e gera alertas/processos';

    public function handle(): int
    {
        $inst = $this->option('instituicao') ? (int) $this->option('instituicao') : null;
        $ano = $this->option('ano') ? (int) $this->option('ano') : null;
        $r = DetectorInfrequencia::executar($inst, $ano);
        $this->info(sprintf(
            'Analisados: %d · Alertas novos: %d · Processos: %d',
            $r['analisados'],
            $r['alertas'],
            $r['processos']
        ));

        return self::SUCCESS;
    }
}
