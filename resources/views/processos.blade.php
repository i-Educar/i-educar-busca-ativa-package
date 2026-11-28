@extends('busca-ativa::layout')
@section('titulo', 'Processos — Busca Ativa')
@section('migalha', 'Processos')
@section('icone-tela', 'fa-folder-open')
@section('titulo-tela', 'Processos de busca ativa')
@section('lead') Acompanhe cada aluno pelas etapas: contato, visita, encaminhamento, acompanhamento e retorno. @endsection
@section('acoes-topo')
    <a class="botao claro icone" href="{{ url('/busca-ativa/alertas') }}" title="Alertas" aria-label="Alertas"><i aria-hidden="true" class="fa fa-bell"></i></a>
@endsection
@section('conteudo')
@php
    use iEducar\Packages\BuritiBuscaAtiva\Support\EtapasProcesso;
@endphp
<div class="buriti-filtro-bloco">
    <h2><i class="fa fa-filter"></i> Filtros</h2>
    <form method="get" class="buriti-filtro" action="{{ url('/busca-ativa/processos') }}">
        <label>Etapa
            <select name="etapa">
                <option value="">Todas</option>
                @foreach($etapas as $e)
                    <option value="{{ $e }}" @selected(($filtroEtapa ?? '') === $e)>{{ EtapasProcesso::rotulo($e) }}</option>
                @endforeach
            </select>
        </label>
        <label>Status
            <select name="status">
                <option value="">Todos</option>
                <option value="aberto" @selected(($filtroStatus ?? '') === 'aberto')>Aberto</option>
                <option value="encerrado" @selected(($filtroStatus ?? '') === 'encerrado')>Encerrado</option>
            </select>
        </label>
        <div class="acoes">
            <button class="botao" type="submit">Aplicar</button>
            <a class="botao claro" href="{{ url('/busca-ativa/processos') }}">Limpar</a>
        </div>
    </form>
</div>
<div class="bloco">
    <h2><i class="fa fa-folder-open"></i> Processos</h2>
    <table>
        <thead>
        <tr>
            <th>#</th>
            <th>Aluno</th>
            <th>Escola</th>
            <th>Etapa</th>
            <th>Status</th>
            <th>Aberto em</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @forelse($processos as $pr)
            <tr>
                <td>{{ $pr->id }}</td>
                <td>{{ $pr->aluno_nome ?: ('#'.$pr->ref_cod_aluno) }}</td>
                <td>{{ $pr->escola_nome ?? '—' }}</td>
                <td><span class="badge warn">{{ EtapasProcesso::rotulo($pr->etapa_atual) }}</span></td>
                <td>
                    @if($pr->status === 'aberto')
                        <span class="badge warn">Aberto</span>
                    @else
                        <span class="badge ok">Encerrado</span>
                    @endif
                </td>
                <td>{{ $pr->aberto_em }}</td>
                <td><a class="botao claro" href="{{ url('/busca-ativa/processos/'.$pr->id) }}">Abrir</a></td>
            </tr>
        @empty
            <tr><td colspan="7">Nenhum processo neste filtro.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
