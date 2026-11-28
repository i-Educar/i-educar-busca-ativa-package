# Busca ativa escolar

Detecta faltas excessivas, abre processos de reintegração e acompanha a família até o retorno do estudante.

Composer: `buriti/i-educar-busca-ativa-package`

## Para que serve

Apoia a política de permanência escolar: regras configuráveis por curso, geração de alertas, processos de busca ativa, acompanhamento familiar (API) e ponte com atendimento.

## Desenho de funções

| Função | Entrega |
| --- | --- |
| Detecção | Motor de faltas consecutivas/excessivas (`busca-ativa:detectar`) |
| Flag por curso | Somente turmas de cursos com Busca Ativa entram no radar |
| Alertas | Lista, detalhe e resolução de alertas |
| Processos | Abertura e atualização do acompanhamento |
| Configuração | Limites e parâmetros da detecção |
| Relatórios / exportação | Visão gerencial da rede |
| API familiar | `/busca-ativa/api/v1` para acompanhamento externo |

## Quem usa

- SME (frequência / assistência)
- Escolas
- Famílias (via API/app)

## Integrações

- Frequência do núcleo / i-Diário
- Atendimento ao estudante
- Apps API

## Compatibilidade

| Produto | Versão |
| --- | --- |
| **i-Educar** | **2.12.x** |
| **i-Diário** | Portabilis i-Diário (API / SSO do núcleo) |
| Pacote | `0.2.1` |

Declarado em `composer.json` → `extra.ieducar` / `extra.idiario`.

## Documentação

| Documento | Uso |
| --- | --- |
| [`EPIC.md`](./EPIC.md) | Escopo de produto, aceite e critérios |
| [`ROADMAP.md`](./ROADMAP.md) | Fases e entregas |
| [`TODO.md`](./TODO.md) | Backlog operacional |
| [`REQUISITOS-TR.md`](./REQUISITOS-TR.md) | Rastreio de requisitos (planejamento) |

---

Licença: **GPL-2.0-or-later** · Organização [i-Educar](https://github.com/i-Educar)
