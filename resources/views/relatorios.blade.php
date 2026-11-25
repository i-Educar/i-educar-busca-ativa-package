@extends('busca-ativa::layout')
@section('titulo', 'Relatórios — Busca Ativa')
@section('migalha', 'Relatórios')
@section('icone-tela', 'fa-bar-chart')
@section('titulo-tela', 'Relatórios')
@section('lead') Síntese de alertas e processos por tipo, status e etapa. @endsection
@section('conteudo')
@php use iEducar\Packages\BuritiBuscaAtiva\Support\EtapasProcesso; @endphp
<div class="bloco">
    <h2>Alertas por tipo</h2>
    <table>
        <thead><tr><th>Tipo</th><th>Total</th></tr></thead>
        <tbody>
        @forelse($porTipo as $r)
            <tr><td>{{ $r->tipo }}</td><td>{{ $r->total }}</td></tr>
        @empty
            <tr><td colspan="2">Sem dados.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="bloco">
    <h2>Processos por status</h2>
    <table>
        <thead><tr><th>Status</th><th>Total</th></tr></thead>
        <tbody>
        @forelse($porStatus as $r)
            <tr><td>{{ $r->status }}</td><td>{{ $r->total }}</td></tr>
        @empty
            <tr><td colspan="2">Sem dados.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="bloco">
    <h2>Processos abertos por etapa</h2>
    <table>
        <thead><tr><th>Etapa</th><th>Total</th></tr></thead>
        <tbody>
        @forelse($porEtapa as $r)
            <tr><td>{{ EtapasProcesso::rotulo($r->etapa_atual) }}</td><td>{{ $r->total }}</td></tr>
        @empty
            <tr><td colspan="2">Sem processos abertos.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
