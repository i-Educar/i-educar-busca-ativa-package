# TODO — Busca Ativa

> Pacote: `buriti/i-educar-busca-ativa-package` · TR **5.3.158 / 5.3.173 / 5.3.204** · Prioridade **P1**

## Onda 1 — scaffold (esta entrega)

- [x] `composer.json` + ServiceProvider + PACKAGE
- [x] Schema `busca_ativa.*` + flag `pmieducar.curso.busca_ativa`
- [x] Menus/processos **882200–882206** + PermissaoMatriz
- [x] Telas: painel, alertas, processos, configuração, tokens, relatórios
- [x] Detector + comando `busca-ativa:detectar`
- [x] API familiar migrada para `/api/apps/v1/familia/busca-ativa/*`
- [x] Ponte Atendimento (`alerta_busca_ativa`)
- [x] EPIC / ROADMAP / TODO / REQUISITOS-TR
- [x] Catálogo Laguna + composer + tenants
- [x] `busca-ativa:demo` — faltas + processos multi-etapa/atores + wire em `laguna:povoar-cidade` / `laguna:setup`

## Backlog

- [ ] Diário diário (faltas consecutivas reais por dia letivo)
- [ ] Notificação push/SMS à família
- [ ] Unificar UI com legado ActiveLooking
- [x] Expor endpoints no `i-educar-apps-api-package` (Sanctum / familia.busca_ativa)
- [ ] Relatório PDF risco de evasão por turma
- [ ] Testes automatizados (`tests/run.php`)
- [ ] Roteiro PoC binário Laguna

## Gate PoC

- [ ] `REQUISITOS-TR.md` 100% classificado
- [ ] ≥ 85% atende com evidência
- [ ] ≤ 15% gap com plano ≤ 30 dias
