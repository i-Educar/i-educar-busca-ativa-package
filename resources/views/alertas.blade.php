@extends('busca-ativa::layout')
@section('titulo', 'Alertas — Busca Ativa')
@section('migalha', 'Alertas')
@section('icone-tela', 'fa-bell')
@section('titulo-tela', 'Alertas de infrequência')
@section('lead') Alertas gerados a partir das faltas dos alunos em cursos elegíveis. @endsection
@section('acoes-topo')
    <form method="post" action="{{ url('/busca-ativa/detectar') }}">@csrf
        <button class="botao icone" type="submit" title="Detectar agora" aria-label="Detectar agora"><i aria-hidden="true" class="fa fa-refresh"></i></button>
    </form>
@endsection
@section('conteudo')
<div class="bloco">
    <h2><i class="fa fa-bell"></i> Alertas</h2>
    <table>
        <thead><tr><th>Aluno</th><th>Escola</th><th>Tipo</th><th>Severidade</th><th>Motivo</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse($alertas as $a)
            <tr>
                <td>{{ $a->aluno_nome ?: ('#'.$a->ref_cod_aluno) }}</td>
                <td>{{ $a->escola_nome ?? '—' }}</td>
                <td>{{ $a->tipo }}</td>
                <td><span class="badge {{ $a->severidade === 'alta' ? 'fail' : 'warn' }}">{{ $a->severidade }}</span></td>
                <td>{{ $a->motivo }}</td>
                <td>{{ $a->status }}</td>
                <td style="white-space:nowrap">
                    <a class="botao claro" href="{{ url('/busca-ativa/alertas/'.$a->id) }}">Detalhes</a>
                    @if($a->status === 'aberto')
                    <form method="post" action="{{ url('/busca-ativa/alertas/'.$a->id.'/resolver') }}" style="display:inline">@csrf
                        <button class="botao claro" type="submit">Resolver</button>
                    </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="7">Nenhum alerta. Rode a detecção ou aguarde faltas.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
