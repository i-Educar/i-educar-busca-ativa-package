@extends('busca-ativa::layout')
@section('titulo', 'Configuração — Busca Ativa')
@section('migalha', 'Configuração')
@section('icone-tela', 'fa-cog')
@section('titulo-tela', 'Regras do módulo')
@section('lead') Limites de faltas, frequência mínima, ponte com atendimento e cursos no radar. @endsection
@section('conteudo')
<form method="post" action="{{ url('/busca-ativa/configuracao') }}">
@csrf
<div class="bloco">
    <h2>Limites (TR 5.3.158 / 5.3.173)</h2>
    <div class="form-grid">
        <label>Faltas consecutivas para alerta
            <input type="number" min="1" name="faltas_consecutivas_alerta" value="{{ $cfg['faltas_consecutivas_alerta'] }}">
        </label>
        <label>Faltas no período (mês)
            <input type="number" min="1" name="faltas_mensais_alerta" value="{{ $cfg['faltas_mensais_alerta'] }}">
        </label>
        <label>Frequência mínima (%)
            <input type="number" step="0.1" min="0" max="100" name="frequencia_minima_percentual" value="{{ $cfg['frequencia_minima_percentual'] }}">
        </label>
        <label>Dias sem contato para escalar
            <input type="number" min="1" name="dias_sem_contato_escalar" value="{{ $cfg['dias_sem_contato_escalar'] }}">
        </label>
    </div>
</div>
<div class="bloco">
    <h2>Comportamento</h2>
    <div class="form-grid">
        <label><input type="checkbox" name="ativo" value="1" @checked($cfg['ativo'] ?? true)> Módulo ativo</label>
        <label><input type="checkbox" name="somente_cursos_marcados" value="1" @checked($cfg['somente_cursos_marcados'] ?? true)> Somente cursos com flag Busca Ativa</label>
        <label><input type="checkbox" name="abrir_processo_automatico" value="1" @checked($cfg['abrir_processo_automatico'] ?? true)> Abrir processo automaticamente</label>
        <label><input type="checkbox" name="gerar_alerta_atendimento" value="1" @checked($cfg['gerar_alerta_atendimento'] ?? true)> Gerar alerta no Atendimento ao estudante</label>
        <label><input type="checkbox" name="notificar_familia" value="1" @checked($cfg['notificar_familia'] ?? true)> Notificar família (API/canal)</label>
    </div>
</div>
<div class="bloco">
    <h2>Cursos no radar</h2>
    <p class="lead" style="margin:0 0 10px">A flag também aparece no cadastro do curso. Aqui você pode marcar em lote.</p>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:8px;max-height:320px;overflow:auto">
        @foreach($cursos as $c)
            <label style="display:flex;gap:8px;align-items:center;font-weight:600;color:var(--tinta)">
                <input type="checkbox" name="cursos_busca_ativa[]" value="{{ $c->cod_curso }}" @checked($c->busca_ativa)>
                {{ $c->nm_curso }}
            </label>
        @endforeach
    </div>
</div>
<button class="botao" type="submit"><i class="fa fa-save"></i> Salvar configuração</button>
</form>
@endsection
