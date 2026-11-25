@extends('busca-ativa::layout')
@section('titulo', 'API — migrada')
@section('migalha', 'API')
@section('icone-tela', 'fa-mobile')
@section('titulo-tela', 'API e tokens estão no módulo Apps')
@section('lead') A autenticação familiar usa Sanctum no Apps da rede. @endsection
@section('conteudo')
<div class="bloco">
    <h2><i class="fa fa-info-circle"></i> Contrato atualizado</h2>
    <p class="sub">Endpoints: <code>/api/apps/v1/familia/busca-ativa/*</code> com perfil <code>familia</code>.</p>
    <div class="acoes-tela" style="margin-top:12px">
        <a class="botao" href="{{ url('/apps-api/configuracao') }}"><i class="fa fa-sliders"></i> Configurar Apps API</a>
        <a class="botao claro" href="{{ url('/apps-api/funcoes') }}"><i class="fa fa-list-alt"></i> Ver funções</a>
        <a class="botao claro" href="{{ url('/apps-api/governanca') }}"><i class="fa fa-shield"></i> Governança / tokens</a>
    </div>
</div>
@endsection
