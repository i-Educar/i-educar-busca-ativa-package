# ROADMAP — Busca Ativa

## Fase 0 — Fundação (feita)

- Pacote Buriti 2.12, schema, ACL, flag de curso, UI operacional, API token, ponte atendimento.

## Fase 1 — PoC Laguna (0–30 dias)

| ID | Entrega | Critério |
| --- | --- | --- |
| BA-1 | Regras configuráveis | Alterar limites e rerodar `busca-ativa:detectar` muda alertas |
| BA-2 | Escopo por curso | Só matrículas de `curso.busca_ativa=true` quando “somente marcados” |
| BA-3 | Processo operacional | Etapas + eventos + encerramento |
| BA-4 | API família | Token Bearer + POST acompanhamento |
| BA-5 | Atendimento | Registro `alerta_busca_ativa` na fila PoC |

## Fase 2 — Produção (≤ 30 dias pós-PoC)

- Faltas consecutivas a partir do diário diário
- Integração apps-api / notificações
- Relatórios oficiais e exportação
- Migração assistida do legado ActiveLooking

## Fase 3 — Evolução

- Painel territorial (rede) e priorização por risco
- Workflow com conselho tutelar / assistência (quando houver convênio)
