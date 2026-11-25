# REQUISITOS-TR — Busca Ativa (recorte 5.3)

| ID | Requisito | Status | Evidência |
| --- | --- | --- | --- |
| 5.3.158 | Comunicar equipe diretiva sobre faltas excessivas (limite configurável) | **atende (PoC)** | Config + detector + alertas |
| 5.3.173 | Definir quantidade de faltas consecutivas que determina busca ativa | **atende (PoC)** | `faltas_consecutivas_alerta` + processo |
| 5.3.204 | Relatório de risco de evasão por turma/escola | **parcial** | Tela relatórios agregada; PDF/turma em gap |
| 5.9.* | Ponte atendimento | **atende (PoC)** | `AtendimentoBridge` → `atendimento.poc_registro` |
| API família | Acompanhamento externo | **atende (PoC)** | `/busca-ativa/api/v1` |
| Curso | Flag alvo busca ativa | **atende** | `pmieducar.curso.busca_ativa` + cadastro + config |

Gaps ≤ 15%: diário diário real; relatório PDF por turma; unificação legado ActiveLooking.
