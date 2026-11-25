@extends('busca-ativa::layout')
@section('titulo', 'Início — Busca Ativa')
@section('migalha', 'Início')
@section('icone-tela', 'fa-search')
@section('titulo-tela', 'Busca Ativa')
@section('lead')
    Detecte infrequência em cursos marcados, abra processos e acompanhe a família.
@endsection
@section('acoes-topo')
    <form method="post" action="{{ url('/busca-ativa/detectar') }}" style="display:inline">
        @csrf
        <button class="botao" type="submit"><i class="fa fa-refresh"></i> Rodar detecção</button>
    </form>
    <a class="botao claro" href="{{ url('/busca-ativa/configuracao') }}"><i class="fa fa-cog"></i> Regras</a>
@endsection
@section('conteudo')
    @include('buriti-ui::painel-corpo', [
        'kpis' => $kpis ?? [],
        'atalhos' => $atalhos ?? [],
        'destaques' => $destaques ?? [],
        'analise' => $analise ?? [],
        'analiseTitulo' => $analiseTitulo ?? 'Análise',
        'resumo' => $resumo ?? null,
        'fluxoTitulo' => $fluxoTitulo ?? 'Fluxo do módulo',
    ])
@endsection
