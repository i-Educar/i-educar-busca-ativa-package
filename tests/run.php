<?php

declare(strict_types=1);

require __DIR__.'/../src/Support/Processos.php';

use iEducar\Packages\BuritiBuscaAtiva\Support\Processos;

$ok = Processos::RAIZ === 882200
    && count(Processos::todos()) === 7
    && Processos::titulos()[Processos::CONFIGURACAO] === 'Configuração';

fwrite(STDOUT, $ok ? "OK busca-ativa processos\n" : "FAIL\n");
exit($ok ? 0 : 1);
