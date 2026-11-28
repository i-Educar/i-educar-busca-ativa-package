@extends('busca-ativa::layout')
@section('titulo', 'Alerta #'.$alerta->id)
@section('migalha', 'Alerta')
@section('icone-tela', 'fa-bell')
@section('titulo-tela', ($alerta->aluno_nome ?? ('Aluno #'.$alerta->ref_cod_aluno)))
@section('lead')
    {{ $alerta->escola_nome ?? 'Rede' }} · {{ $alerta->tipo }} · {{ $alerta->status }}
@endsection
@section('acoes-topo')
    <a class="botao claro" href="{{ url('/busca-ativa/alertas') }}">Voltar</a>
    @if(!empty($processoId))
        <a class="botao icone" href="{{ url('/busca-ativa/processos/'.$processoId) }}" title="Processo" aria-label="Processo"><i aria-hidden="true" class="fa fa-folder-open"></i></a>
    @endif
    @if(($alerta->status ?? '') === 'aberto')
        <form method="post" action="{{ url('/busca-ativa/alertas/'.$alerta->id.'/resolver') }}">@csrf
            <button class="botao claro" type="submit">Resolver</button>
        </form>
        <a class="botao icone" href="{{ url('/atendimento-estudante/registro?'.http_build_query([
            'aluno_id' = title="$alerta->ref_cod_aluno, 'aluno' => $alerta->aluno_nome, 'motivo' => $alerta->motivo, 'alerta_id' => $alerta->id, ])) }}&quot;> Atender família" aria-label="$alerta->ref_cod_aluno, 'aluno' => $alerta->aluno_nome, 'motivo' => $alerta->motivo, 'alerta_id' => $alerta->id, ])) }}&quot;> Atender família">$alerta->ref_cod_aluno,
            'aluno' => $alerta->aluno_nome,
            'motivo' => $alerta->motivo,
            'alerta_id' => $alerta->id,
        ])) }}"><i aria-hidden="true" class="fa fa-handshake-o"></i></a>
    @endif
@endsection
@section('conteudo')
@include('busca-ativa::partials.espelho-chamada', ['espelho' => $espelho ?? null])

<div class="bloco">
    <h2>Dados do alerta</h2>
    <dl class="form-grid">
        <div><strong>Detectado em</strong><br>{{ $alerta->detectado_em }}</div>
        <div><strong>Severidade</strong><br>
            <span class="badge {{ ($alerta->severidade ?? '') === 'alta' ? 'fail' : 'warn' }}">{{ $alerta->severidade }}</span>
        </div>
        <div><strong>Faltas consecutivas</strong><br>{{ $alerta->faltas_consecutivas ?? '—' }}</div>
        <div><strong>Faltas no período</strong><br>{{ $alerta->faltas_periodo ?? '—' }}</div>
        <div><strong>Frequência</strong><br>
            @if(isset($alerta->frequencia_percentual))
                {{ number_format((float) $alerta->frequencia_percentual, 1, ',', '.') }}%
            @else
                —
            @endif
        </div>
        <div class="col-wide"><strong>Motivo</strong><br>{{ $alerta->motivo ?? '—' }}</div>
    </dl>
</div>
@endsection
