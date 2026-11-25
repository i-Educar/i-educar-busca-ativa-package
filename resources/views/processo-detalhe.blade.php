@extends('busca-ativa::layout')
@section('titulo', 'Processo #'.$processo->id)
@section('migalha', 'Processo')
@section('icone-tela', 'fa-folder-open')
@section('titulo-tela', 'Processo #'.$processo->id.' — '.($processo->aluno_nome ?? ('Aluno #'.$processo->ref_cod_aluno)))
@section('lead') {{ $processo->escola_nome ?? 'Rede' }} · Etapa: {{ \iEducar\Packages\BuritiBuscaAtiva\Support\EtapasProcesso::rotulo($processo->etapa_atual) }} · {{ $processo->status }} @endsection
@section('acoes-topo')
    <a class="botao claro" href="{{ url('/busca-ativa/processos') }}">Voltar</a>
@endsection
@section('conteudo')
@include('busca-ativa::partials.espelho-chamada', ['espelho' => $espelho ?? null])

<div class="bloco">
    <h2>Atualizar processo</h2>
    <form method="post" action="{{ url('/busca-ativa/processos/'.$processo->id) }}" class="form-grid">
        @csrf
        <label>Ação
            <select name="acao">
                <option value="anotacao">Anotação / contato</option>
                <option value="etapa">Mudar etapa</option>
                <option value="encerrar">Encerrar</option>
            </select>
        </label>
        <label>Etapa
            <select name="etapa">
                @foreach(\iEducar\Packages\BuritiBuscaAtiva\Support\EtapasProcesso::todas() as $e)
                    <option value="{{ $e }}" @selected($processo->etapa_atual===$e)>
                        {{ \iEducar\Packages\BuritiBuscaAtiva\Support\EtapasProcesso::rotulo($e) }}
                    </option>
                @endforeach
            </select>
        </label>
        <label>Resultado (encerrar)
            <select name="resultado">
                <option value="retorno">Retorno às aulas</option>
                <option value="transferencia">Transferência</option>
                <option value="abandono">Abandono confirmado</option>
                <option value="outro">Outro</option>
            </select>
        </label>
        <label>Canal<input name="canal" placeholder="telefone, visita, api…"></label>
        <label style="grid-column:1/-1">Observações<textarea name="observacoes" rows="3">{{ $processo->observacoes }}</textarea></label>
        <button class="botao" type="submit">Salvar</button>
    </form>
</div>
<div class="bloco">
    <h2>Linha do tempo</h2>
    <table>
        <thead><tr><th>Quando</th><th>Tipo</th><th>Canal</th><th>Descrição</th></tr></thead>
        <tbody>
        @forelse($eventos as $ev)
            <tr><td>{{ $ev->created_at }}</td><td>{{ $ev->tipo }}</td><td>{{ $ev->canal ?? '—' }}</td><td>{{ $ev->descricao }}</td></tr>
        @empty
            <tr><td colspan="4">Sem eventos.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="bloco">
    <h2>Acompanhamento familiar (API)</h2>
    <table>
        <thead><tr><th>Quando</th><th>Canal</th><th>Status</th><th>Retorno</th></tr></thead>
        <tbody>
        @forelse($familia as $f)
            <tr><td>{{ $f->created_at }}</td><td>{{ $f->canal }}</td><td>{{ $f->status_contato }}</td><td>{{ $f->retorno_familia }}</td></tr>
        @empty
            <tr><td colspan="4">Nenhum retorno familiar ainda.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
