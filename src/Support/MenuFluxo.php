<?php

namespace iEducar\Packages\BuritiBuscaAtiva\Support;

final class MenuFluxo
{
    /**
     * @param  array<string, bool>  $permite
     * @return list<array{nome:string,itens:list<array{rotulo:string,url:string,icone:string,chave:string}>}>
     */
    public static function grupos(array $permite): array
    {
        $item = static function (string $chave, string $rotulo, string $url, string $icone) use ($permite): ?array {
            if (! ($permite[$chave] ?? false)) {
                return null;
            }

            return compact('chave', 'rotulo', 'url', 'icone');
        };
        $filtrar = static fn (array $itens): array => array_values(array_filter($itens));

        return array_values(array_filter([
            [
                'nome' => 'Operação',
                'itens' => $filtrar([
                    $item('painel', 'Início', '/busca-ativa', 'fa-th-large'),
                    $item('alertas', 'Alertas', '/busca-ativa/alertas', 'fa-bell'),
                    $item('processos', 'Processos', '/busca-ativa/processos', 'fa-folder-open'),
                ]),
            ],
            [
                'nome' => 'Governança',
                'itens' => $filtrar([
                    $item('configuracao', 'Regras', '/configuracoes/modulos?modulo=busca-ativa', 'fa-cog'),
                    $item('relatorios', 'Relatórios', '/busca-ativa/relatorios', 'fa-bar-chart'),
                ]),
            ],
        ], static fn (array $g): bool => $g['itens'] !== []));
    }
}
