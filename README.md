# Busca Ativa Escolar (Buriti)

Pacote `buriti/i-educar-busca-ativa-package` para o i-Educar 2.12.

## Compatibilidade

| Produto | Versão |
| --- | --- |
| **i-Educar** | **2.12.x** (min. 2.12.0) |
| **i-Diário** | Portabilis i-Diário (espelho Laguna) via API/SSO do core |
| Pacote | `0.2.0` |

Declarado em `composer.json` → `extra.ieducar` e `extra.idiario`.

## Rotas

- SME: `/busca-ativa`
- API: `/busca-ativa/api/v1` (Bearer / `X-Busca-Ativa-Token`)

## Comando

```bash
php artisan busca-ativa:detectar [--instituicao=] [--ano=]
```

## Documentos

- `EPIC.md` · `ROADMAP.md` · `TODO.md` · `REQUISITOS-TR.md`
