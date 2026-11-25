@php
    /** @var array|null $espelho */
    $espelho = $espelho ?? null;
@endphp
@if(!empty($espelho))
<style>
    .espelho-chamada { margin: 0 0 12px; }
    .espelho-chamada h2 { margin: 0 0 8px; display:flex; align-items:center; gap:8px; }
    .espelho-ind { display:grid; grid-template-columns:repeat(auto-fit,minmax(120px,1fr)); gap:8px; margin:0 0 12px; }
    .espelho-ind article {
        border:1px solid var(--linha,#c9d6df); border-radius:12px; padding:10px 12px;
        background:linear-gradient(180deg,#f7fafb,#fff);
    }
    .espelho-ind span { display:block; font-size:.68rem; font-weight:800; letter-spacing:.06em; text-transform:uppercase; color:var(--suave,#5b6b76); }
    .espelho-ind strong { font-size:1.15rem; font-weight:800; letter-spacing:-.02em; }
    .espelho-grade {
        display:grid; grid-template-columns:repeat(auto-fill,minmax(42px,1fr)); gap:4px; margin:0 0 10px;
    }
    .espelho-dia {
        aspect-ratio:1; border-radius:8px; border:1px solid var(--linha,#c9d6df);
        display:flex; flex-direction:column; align-items:center; justify-content:center;
        font-size:.62rem; font-weight:700; color:var(--suave,#5b6b76); background:#fff;
    }
    .espelho-dia em { font-style:normal; font-size:.72rem; font-weight:800; color:var(--tinta,#1a242b); }
    .espelho-dia.falta { background:#fdeceb; border-color:#e8b4b4; color:#9b2c2c; }
    .espelho-dia.falta em { color:#9b2c2c; }
    .espelho-dia.ok { background:#e5f6ec; border-color:#b7e0c5; }
    .espelho-legenda { margin:0 0 12px; color:var(--suave,#5b6b76); font-size:.78rem; line-height:1.4; }
    .espelho-etapas { width:100%; border-collapse:collapse; }
    .espelho-etapas th, .espelho-etapas td { text-align:left; padding:8px 10px; border-bottom:1px solid var(--linha,#c9d6df); font-size:.84rem; }
    .espelho-etapas th { background:var(--clara,#e8eef2); color:var(--cor,#16384a); }
    .espelho-barras { display:inline-flex; gap:2px; vertical-align:middle; }
    .espelho-barras i { width:8px; height:14px; border-radius:2px; background:#c0392b; display:inline-block; }
</style>
<div class="bloco espelho-chamada" aria-label="Espelho da chamada">
    <h2><i class="fa fa-calendar-check-o" style="color:var(--destaque,#1f4e66)"></i> Espelho da chamada</h2>
    <p class="espelho-legenda">{{ $espelho['legenda'] ?? '' }}</p>

    <div class="espelho-ind">
        <article>
            <span>Consecutivas</span>
            <strong>{{ $espelho['indicadores']['faltas_consecutivas'] ?? 0 }}</strong>
        </article>
        <article>
            <span>No período</span>
            <strong>{{ $espelho['indicadores']['faltas_periodo'] ?? 0 }}</strong>
        </article>
        <article>
            <span>Frequência</span>
            <strong>
                @if(isset($espelho['indicadores']['frequencia_percentual']))
                    {{ number_format((float) $espelho['indicadores']['frequencia_percentual'], 1, ',', '.') }}%
                @else
                    —
                @endif
            </strong>
        </article>
        <article>
            <span>Tipo do alerta</span>
            <strong style="font-size:.9rem">{{ $espelho['indicadores']['tipo'] ?? '—' }}</strong>
        </article>
    </div>

    @if(!empty($espelho['indicadores']['motivo']))
        <p style="margin:0 0 12px"><strong>Motivo:</strong> {{ $espelho['indicadores']['motivo'] }}</p>
    @endif

    <p style="margin:0 0 6px;font-weight:800;font-size:.78rem;letter-spacing:.06em;text-transform:uppercase;color:var(--suave,#5b6b76)">Dias úteis recentes</p>
    <div class="espelho-grade" role="list">
        @foreach(($espelho['dias'] ?? []) as $dia)
            <div class="espelho-dia {{ !empty($dia['falta']) ? 'falta' : 'ok' }}" role="listitem" title="{{ $dia['data'] }}{{ !empty($dia['falta']) ? ' · falta' : ' · presente / sem falta registrada' }}">
                <span>{{ $dia['label'] }}</span>
                <em>{{ !empty($dia['falta']) ? 'F' : '·' }}</em>
            </div>
        @endforeach
    </div>

    @if(!empty($espelho['etapas']))
        <p style="margin:12px 0 6px;font-weight:800;font-size:.78rem;letter-spacing:.06em;text-transform:uppercase;color:var(--suave,#5b6b76)">Totais por etapa (diário)</p>
        <table class="espelho-etapas">
            <thead>
                <tr><th>Etapa</th><th>Componente</th><th>Faltas</th><th></th></tr>
            </thead>
            <tbody>
            @foreach($espelho['etapas'] as $e)
                <tr>
                    <td>{{ $e['etapa'] }}</td>
                    <td>{{ $e['componente'] }}</td>
                    <td><strong>{{ $e['quantidade'] }}</strong></td>
                    <td>
                        <span class="espelho-barras" aria-hidden="true">
                            @for($i = 0; $i < min(12, (int) $e['quantidade']); $i++)
                                <i></i>
                            @endfor
                        </span>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @else
        <p class="espelho-legenda">Sem totais por etapa lançados ainda para esta matrícula.</p>
    @endif
</div>
@endif
