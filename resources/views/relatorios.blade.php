@extends('busca-ativa::layout')
@section('titulo', 'Relatórios — Busca Ativa')
@section('migalha', 'Relatórios')
@section('icone-tela', 'fa-bar-chart')
@section('titulo-tela', 'Gestão da permanência escolar')
@section('lead')
    Painel de indicadores da Busca Ativa — risco, fluxo processual e efetividade do retorno às aulas · atualizado em {{ $atualizadoEm ?? now()->format('d/m/Y H:i') }}
@endsection
@section('acoes-topo')
    @include('exportacao::partials.botoes-exportar', [
        'action' => url('/busca-ativa/relatorios/exportar'),
        'formatos' => ['xlsx', 'csv', 'pdf'],
        'formatoPadrao' => 'xlsx',
        'opcoes' => [
            ['value' => 'alunos', 'label' => 'Lista de alunos (Excel)'],
            ['value' => 'sintese', 'label' => 'Síntese gerencial (tipo / status / etapa / qualidade)'],
        ],
        'tituloModal' => 'Exportar — Busca Ativa',
        'leadModal' => 'Baixe a lista de alunos em Excel para acompanhamento da rede, ou a síntese gerencial em PDF/Excel.',
        'rotulo' => 'Exportar Excel / PDF',
    ])
    <a class="botao claro icone" href="{{ url('/busca-ativa/alertas') }}" title="Alertas" aria-label="Alertas"><i aria-hidden="true" class="fa fa-bell"></i></a>
@endsection

@section('conteudo')
@php
    $maxBar = static function (array $serie): int {
        $m = 0;
        foreach ($serie as $item) {
            $m = max($m, (int) ($item['total'] ?? 0));
        }
        return max(1, $m);
    };
    $pct = static function (int $valor, int $maximo): float {
        return round(($valor / max(1, $maximo)) * 100, 1);
    };
    $cores = ['#0e7490', '#c74634', '#a85f00', '#17663f', '#5b21b6', '#0369a1', '#b45309'];
@endphp

<style>
    .ba-leitura {
        background: linear-gradient(135deg, color-mix(in srgb, var(--clara) 75%, #fff), #fff);
        border: 1px solid var(--linha); border-left: 4px solid var(--cor-tela);
        border-radius: 14px; padding: 14px 16px; margin: 0 0 16px;
    }
    .ba-leitura h2 { margin: 0 0 8px; font-size: 1rem; display: flex; align-items: center; gap: 8px; }
    .ba-leitura ol { margin: 0; padding-left: 18px; color: var(--tinta); }
    .ba-leitura li { margin: 0 0 6px; line-height: 1.45; font-size: .92rem; }
    .ba-grade-2 {
        display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; margin: 0 0 12px;
    }
    .ba-grade-2 .bloco { margin: 0; }
    .ba-qualidade {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px; margin: 0 0 16px;
    }
    .ba-qi {
        background: #fff; border: 1px solid var(--linha); border-radius: 14px; padding: 14px;
        border-top: 3px solid var(--linha); box-shadow: 0 8px 18px rgba(21,34,43,.04);
    }
    .ba-qi.ok { border-top-color: var(--ok); }
    .ba-qi.warn { border-top-color: var(--ambar); }
    .ba-qi.fail { border-top-color: var(--erro); }
    .ba-qi .rotulo { display:block; font-size:.72rem; font-weight:800; letter-spacing:.04em; text-transform:uppercase; color:var(--suave); }
    .ba-qi strong { display:block; font-size:1.45rem; margin:4px 0; letter-spacing:-.03em; }
    .ba-qi .meta { margin:0 0 8px; font-size:.78rem; color:var(--suave); }
    .ba-qi .trilha { height:8px; border-radius:999px; background:#eef1f3; overflow:hidden; }
    .ba-qi .trilha > span { display:block; height:100%; border-radius:999px; background:var(--cor-tela); }
    .ba-qi.ok .trilha > span { background: var(--ok); }
    .ba-qi.warn .trilha > span { background: var(--ambar); }
    .ba-qi.fail .trilha > span { background: var(--erro); }
    .ba-bars { list-style:none; margin:0; padding:0; display:grid; gap:10px; }
    .ba-bars li { display:grid; grid-template-columns: minmax(90px, 34%) 1fr auto; gap:10px; align-items:center; }
    .ba-bars .lab { font-size:.82rem; font-weight:650; color:var(--tinta); line-height:1.25; }
    .ba-bars .trilha { height:12px; border-radius:999px; background:#eef1f3; overflow:hidden; }
    .ba-bars .trilha > span { display:block; height:100%; border-radius:999px; min-width:2px; transition:width .4s ease; }
    .ba-bars .val { font-size:.85rem; font-weight:800; color:var(--suave); min-width:2ch; text-align:right; }
    .ba-donut-wrap { display:flex; gap:18px; align-items:center; flex-wrap:wrap; }
    .ba-donut { width:132px; height:132px; border-radius:50%; flex:none; position:relative; }
    .ba-donut::after {
        content:attr(data-centro); position:absolute; inset:28px; border-radius:50%;
        background:#fff; display:grid; place-items:center; font-weight:800; font-size:.95rem; color:var(--tinta);
        text-align:center; line-height:1.15; padding:4px;
    }
    .ba-legenda { list-style:none; margin:0; padding:0; display:grid; gap:6px; flex:1; min-width:140px; }
    .ba-legenda li { display:flex; align-items:center; gap:8px; font-size:.82rem; }
    .ba-legenda i { width:10px; height:10px; border-radius:3px; flex:none; }
    .numeros article small { display:block; margin-top:4px; color:var(--suave); font-size:.72rem; font-weight:500; text-transform:none; letter-spacing:0; }
    .ba-sec { margin: 18px 0 8px; font-size:.72rem; font-weight:800; letter-spacing:.1em; text-transform:uppercase; color:var(--suave); }
    .ba-alunos-meta { margin:0 0 10px; color:var(--suave); font-size:.86rem; }
    .ba-alunos td .badge { vertical-align: middle; }
    @media (max-width: 900px) { .ba-grade-2 { grid-template-columns: 1fr; } }
</style>

<section class="ba-leitura" aria-label="Leitura gerencial">
    <h2><i class="fa fa-compass"></i> Leitura para a gestão educacional</h2>
    <ol>
        @foreach(($leitura ?? []) as $texto)
            <li>{{ $texto }}</li>
        @endforeach
    </ol>
</section>

<p class="ba-sec">Indicadores operacionais</p>
<div class="numeros">
    @foreach(($kpis ?? []) as $kpi)
        <article style="--tom: {{ $kpi['tom'] }}">
            <div class="kpi-ico"><i class="fa {{ $kpi['icone'] }}"></i></div>
            <span>{{ $kpi['rotulo'] }}</span>
            <strong>{{ $kpi['valor'] }}</strong>
            @if(!empty($kpi['ajuda']))
                <small>{{ $kpi['ajuda'] }}</small>
            @endif
        </article>
    @endforeach
</div>

<p class="ba-sec">Qualidade do acompanhamento</p>
<div class="ba-qualidade">
    @foreach(($qualidade ?? []) as $q)
        <article class="ba-qi {{ $q['tom'] }}">
            <span class="rotulo">{{ $q['rotulo'] }}</span>
            <strong>{{ $q['valor'] }}</strong>
            <p class="meta">{{ $q['meta'] }}</p>
            <div class="trilha" aria-hidden="true"><span style="width: {{ $q['pct'] }}%"></span></div>
        </article>
    @endforeach
</div>

<p class="ba-sec">Visão gráfica</p>
<div class="ba-grade-2">
    <section class="bloco">
        <h2><i class="fa fa-pie-chart"></i> Alertas por tipo</h2>
        @php
            $serieTipo = $porTipo ?? [];
            $totalTipo = max(1, array_sum(array_column($serieTipo, 'total')));
            $acc = 0;
            $stops = [];
            foreach ($serieTipo as $i => $item) {
                $ini = $acc;
                $acc += ($item['total'] / $totalTipo) * 100;
                $stops[] = ($cores[$i % count($cores)]).' '.$ini.'% '.$acc.'%';
            }
            if ($stops === []) { $stops[] = '#eef1f3 0% 100%'; }
        @endphp
        <div class="ba-donut-wrap">
            <div class="ba-donut" data-centro="{{ array_sum(array_column($serieTipo, 'total')) }}&#10;alertas"
                 style="background: conic-gradient({{ implode(', ', $stops) }})"></div>
            <ul class="ba-legenda">
                @forelse($serieTipo as $i => $item)
                    <li><i style="background:{{ $cores[$i % count($cores)] }}"></i> {{ $item['chave'] }} · <strong>{{ $item['total'] }}</strong></li>
                @empty
                    <li>Sem alertas registrados.</li>
                @endforelse
            </ul>
        </div>
    </section>

    <section class="bloco">
        <h2><i class="fa fa-heartbeat"></i> Severidade (todos os alertas)</h2>
        @php $maxSev = $maxBar($porSeveridade ?? []); @endphp
        <ul class="ba-bars">
            @forelse(($porSeveridade ?? []) as $i => $item)
                <li>
                    <span class="lab">{{ $item['chave'] }}</span>
                    <div class="trilha"><span style="width:{{ $pct($item['total'], $maxSev) }}%;background:{{ $cores[$i % count($cores)] }}"></span></div>
                    <span class="val">{{ $item['total'] }}</span>
                </li>
            @empty
                <li><span class="lab">Sem dados</span><div class="trilha"></div><span class="val">0</span></li>
            @endforelse
        </ul>
    </section>

    <section class="bloco">
        <h2><i class="fa fa-sitemap"></i> Processos abertos por etapa</h2>
        @php $maxEtapa = $maxBar($porEtapa ?? []); @endphp
        <ul class="ba-bars">
            @foreach(($porEtapa ?? []) as $i => $item)
                <li>
                    <span class="lab">{{ $item['rotulo'] }}</span>
                    <div class="trilha"><span style="width:{{ $pct($item['total'], $maxEtapa) }}%;background:{{ $cores[$i % count($cores)] }}"></span></div>
                    <span class="val">{{ $item['total'] }}</span>
                </li>
            @endforeach
        </ul>
        <p style="margin:12px 0 0;font-size:.8rem;color:var(--suave)">
            O funil ideal concentra casos no contato inicial e reduz volume nas etapas finais — gargalo em visita ou encaminhamento pede reforço de equipe.
        </p>
    </section>

    <section class="bloco">
        <h2><i class="fa fa-building"></i> Alertas abertos por escola</h2>
        @php $maxEsc = $maxBar($porEscola ?? []); @endphp
        <ul class="ba-bars">
            @forelse(($porEscola ?? []) as $i => $item)
                <li>
                    <span class="lab">{{ $item['chave'] }}</span>
                    <div class="trilha"><span style="width:{{ $pct($item['total'], $maxEsc) }}%;background:{{ $cores[$i % count($cores)] }}"></span></div>
                    <span class="val">{{ $item['total'] }}</span>
                </li>
            @empty
                <li><span class="lab">Nenhuma escola com alerta aberto</span><div class="trilha"></div><span class="val">0</span></li>
            @endforelse
        </ul>
    </section>
</div>

<div class="ba-grade-2">
    <section class="bloco">
        <h2><i class="fa fa-flag"></i> Status dos alertas</h2>
        @php $maxStA = $maxBar($porStatusAlerta ?? []); @endphp
        <ul class="ba-bars">
            @forelse(($porStatusAlerta ?? []) as $i => $item)
                <li>
                    <span class="lab">{{ $item['chave'] }}</span>
                    <div class="trilha"><span style="width:{{ $pct($item['total'], $maxStA) }}%;background:{{ $cores[$i % count($cores)] }}"></span></div>
                    <span class="val">{{ $item['total'] }}</span>
                </li>
            @empty
                <li><span class="lab">Sem dados</span><div class="trilha"></div><span class="val">0</span></li>
            @endforelse
        </ul>
    </section>
    <section class="bloco">
        <h2><i class="fa fa-check-square-o"></i> Status / resultado dos processos</h2>
        @php $maxStP = $maxBar($porStatusProcesso ?? []); @endphp
        <ul class="ba-bars" style="margin-bottom:12px">
            @forelse(($porStatusProcesso ?? []) as $i => $item)
                <li>
                    <span class="lab">{{ $item['chave'] }}</span>
                    <div class="trilha"><span style="width:{{ $pct($item['total'], $maxStP) }}%;background:{{ $cores[$i % count($cores)] }}"></span></div>
                    <span class="val">{{ $item['total'] }}</span>
                </li>
            @empty
                <li><span class="lab">Sem processos</span><div class="trilha"></div><span class="val">0</span></li>
            @endforelse
        </ul>
        @if(!empty($porResultado))
            <p style="margin:0 0 8px;font-size:.78rem;font-weight:700;color:var(--suave);text-transform:uppercase;letter-spacing:.04em">Resultados de encerramento</p>
            @php $maxRes = $maxBar($porResultado); @endphp
            <ul class="ba-bars">
                @foreach($porResultado as $i => $item)
                    <li>
                        <span class="lab">{{ $item['chave'] }}</span>
                        <div class="trilha"><span style="width:{{ $pct($item['total'], $maxRes) }}%;background:{{ $cores[$i % count($cores)] }}"></span></div>
                        <span class="val">{{ $item['total'] }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</div>

<p class="ba-sec">Lista de alunos</p>
<section class="bloco">
    <h2><i class="fa fa-list"></i> Estudantes no radar</h2>
    <p class="ba-alunos-meta">
        Amostra dos {{ count($alunos ?? []) }} registros mais críticos (abertos e severidade alta primeiro).
        Use <strong>Exportar Excel</strong> para a lista completa da rede.
    </p>
    <div style="overflow:auto">
        <table class="ba-alunos">
            <thead>
                <tr>
                    <th>Aluno</th>
                    <th>Escola</th>
                    <th>Tipo</th>
                    <th>Severidade</th>
                    <th>Freq.</th>
                    <th>Etapa</th>
                    <th>Prazo</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse(($alunos ?? []) as $a)
                <tr>
                    <td>
                        <strong>{{ $a['aluno'] }}</strong>
                        <div style="font-size:.75rem;color:var(--suave)">Matrícula {{ $a['matricula'] }} · alerta #{{ $a['alerta_id'] }}</div>
                    </td>
                    <td>{{ $a['escola'] }}</td>
                    <td>{{ $a['tipo'] }}</td>
                    <td>
                        <span class="badge {{ $a['severidade'] === 'alta' ? 'fail' : ($a['severidade'] === 'media' ? 'warn' : 'ok') }}">
                            {{ $a['severidade'] }}
                        </span>
                    </td>
                    <td>{{ $a['frequencia'] !== null ? $a['frequencia'].'%' : '—' }}</td>
                    <td>{{ $a['etapa'] }}</td>
                    <td>
                        @if(!empty($a['fora_prazo']))
                            <span class="badge fail">Fora do prazo</span>
                        @elseif(!empty($a['prazo_contato']))
                            <span style="font-size:.8rem">{{ $a['prazo_contato'] }}</span>
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        <a class="botao claro" href="{{ url('/busca-ativa/alertas/'.$a['alerta_id']) }}" style="padding:4px 8px;font-size:.78rem">Ver</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8">Nenhum aluno no radar. Execute a detecção após o registro de faltas.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
