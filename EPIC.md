# EPIC — Busca Ativa Escolar

| Campo | Valor |
| --- | --- |
| Pacote | `buriti/i-educar-busca-ativa-package` |
| TR Laguna | **5.3.158 · 5.3.173 · 5.3.204** (+ ponte 5.9 / apps) |
| Prioridade | **P1** |
| Nota atual (executivo) | **D → C (scaffold)** |
| Fonte | `tmp/edital-ocr/MATRIZ-REQUISITOS-TR.md` |
| Regra de ouro | Pregão Eletrônico 77/2026 · Laguna/SC · PoC ≥ 85% por módulo · gaps ≤ 30 dias |
| Base atual | Legado `pmieducar.busca_ativa` (ActiveLooking) + novo schema `busca_ativa.*` |

## Objetivo

Detectar alunos com faltas excessivas/consecutivas conforme regras configuráveis, abrir processos de busca ativa, permitir acompanhamento familiar via API e gerar alertas para o módulo de Atendimento ao estudante. Cursos passam a ter flag **Busca Ativa**; apenas turmas desses cursos entram no radar quando a regra estiver ligada.

## Padrão técnico (i-Educar 2.12)

| Item | Valor |
| --- | --- |
| Alvo | i-Educar **2.12** · PHP **≥ 8.3** · Illuminate **^11|^12|^13** |
| Composer | `buriti/i-educar-busca-ativa-package` |
| Namespace | `iEducar\Packages\BuritiBuscaAtiva\` → `src/` |
| Compat | `extra.ieducar.compatible: ["2.12"]` · GPL-2.0-or-later |
| Schema | `busca_ativa` (+ coluna `pmieducar.curso.busca_ativa`) |
| Processos | **882200–882206** |
| Rotas | `/busca-ativa` (SME) · `/busca-ativa/api/v1` (família) |
| Tenancy | `PackageTenant::allows('buriti/i-educar-busca-ativa-package')` |
| Referência | `packages/buriti/PADRAO-PACOTE-2.12.md` |

## Épicos filhos

1. Configuração de regras (faltas consecutivas, período, frequência mínima, escopo por curso).
2. Detector de infrequência + alertas + abertura de processo.
3. Fluxo operacional de processo (etapas, eventos, encerramento).
4. API familiar (tokens, acompanhamento, consulta de alertas).
5. Ponte com Atendimento ao estudante (`atendimento.poc_registro`).
6. Relatório de risco de evasão / consolidação para gestores.

## Critérios de aceite (PoC)

- [ ] Curso com flag Busca Ativa inclui turmas/matrículas no detector
- [ ] Configuração de limites altera o comportamento da detecção
- [ ] Alertas abrem processo e publicam no atendimento (quando habilitado)
- [ ] API familiar autentica por token e registra acompanhamento
- [ ] Módulo listado no `CatalogoModulos` + permissões `menu_tipo_usuario`
- [ ] Checklist [`REQUISITOS-TR.md`](./REQUISITOS-TR.md) classificado

## Fora de escopo deste EPIC

- Substituição completa do legado `pmieducar.busca_ativa` / ActiveLooking (convivência nesta onda).
- App mobile nativo (consome a API via apps-api em onda futura).
