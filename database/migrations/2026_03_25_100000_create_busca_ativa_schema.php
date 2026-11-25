<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE SCHEMA IF NOT EXISTS busca_ativa');

        if (! Schema::hasTable('busca_ativa.configuracao')) {
            DB::statement(<<<'SQL'
                CREATE TABLE busca_ativa.configuracao (
                    id BIGSERIAL PRIMARY KEY,
                    ref_cod_instituicao INTEGER NOT NULL,
                    faltas_consecutivas_alerta INTEGER NOT NULL DEFAULT 3,
                    faltas_mensais_alerta INTEGER NOT NULL DEFAULT 5,
                    frequencia_minima_percentual NUMERIC(5,2) NOT NULL DEFAULT 75.00,
                    dias_sem_contato_escalar INTEGER NOT NULL DEFAULT 7,
                    gerar_alerta_atendimento BOOLEAN NOT NULL DEFAULT TRUE,
                    notificar_familia BOOLEAN NOT NULL DEFAULT TRUE,
                    somente_cursos_marcados BOOLEAN NOT NULL DEFAULT TRUE,
                    abrir_processo_automatico BOOLEAN NOT NULL DEFAULT TRUE,
                    regras_json JSONB NOT NULL DEFAULT '{}'::jsonb,
                    ativo BOOLEAN NOT NULL DEFAULT TRUE,
                    created_at TIMESTAMP WITHOUT TIME ZONE NULL,
                    updated_at TIMESTAMP WITHOUT TIME ZONE NULL,
                    CONSTRAINT busca_ativa_config_instituicao_uk UNIQUE (ref_cod_instituicao)
                )
            SQL);
        }

        if (! Schema::hasTable('busca_ativa.alerta')) {
            DB::statement(<<<'SQL'
                CREATE TABLE busca_ativa.alerta (
                    id BIGSERIAL PRIMARY KEY,
                    ref_cod_instituicao INTEGER NOT NULL,
                    ref_cod_escola INTEGER NULL,
                    ref_cod_curso INTEGER NULL,
                    ref_cod_turma INTEGER NULL,
                    ref_cod_matricula INTEGER NOT NULL,
                    ref_cod_aluno INTEGER NOT NULL,
                    tipo VARCHAR(40) NOT NULL,
                    severidade VARCHAR(20) NOT NULL DEFAULT 'media',
                    status VARCHAR(30) NOT NULL DEFAULT 'aberto',
                    faltas_consecutivas INTEGER NULL,
                    faltas_periodo INTEGER NULL,
                    frequencia_percentual NUMERIC(5,2) NULL,
                    motivo TEXT NULL,
                    metadados JSONB NOT NULL DEFAULT '{}'::jsonb,
                    atendimento_poc_id BIGINT NULL,
                    detectado_em TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
                    resolvido_em TIMESTAMP WITHOUT TIME ZONE NULL,
                    created_at TIMESTAMP WITHOUT TIME ZONE NULL,
                    updated_at TIMESTAMP WITHOUT TIME ZONE NULL
                )
            SQL);
            DB::statement('CREATE INDEX IF NOT EXISTS busca_ativa_alerta_status_idx ON busca_ativa.alerta (status, detectado_em DESC)');
            DB::statement('CREATE INDEX IF NOT EXISTS busca_ativa_alerta_matricula_idx ON busca_ativa.alerta (ref_cod_matricula)');
            DB::statement('CREATE INDEX IF NOT EXISTS busca_ativa_alerta_aluno_idx ON busca_ativa.alerta (ref_cod_aluno)');
        }

        if (! Schema::hasTable('busca_ativa.processo')) {
            DB::statement(<<<'SQL'
                CREATE TABLE busca_ativa.processo (
                    id BIGSERIAL PRIMARY KEY,
                    alerta_id BIGINT NULL REFERENCES busca_ativa.alerta(id) ON DELETE SET NULL,
                    ref_cod_instituicao INTEGER NOT NULL,
                    ref_cod_escola INTEGER NULL,
                    ref_cod_matricula INTEGER NOT NULL,
                    ref_cod_aluno INTEGER NOT NULL,
                    status VARCHAR(30) NOT NULL DEFAULT 'aberto',
                    etapa_atual VARCHAR(40) NOT NULL DEFAULT 'contato_inicial',
                    responsavel_id INTEGER NULL,
                    aberto_em TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
                    prazo_contato_em TIMESTAMP WITHOUT TIME ZONE NULL,
                    encerrado_em TIMESTAMP WITHOUT TIME ZONE NULL,
                    resultado VARCHAR(40) NULL,
                    observacoes TEXT NULL,
                    metadados JSONB NOT NULL DEFAULT '{}'::jsonb,
                    created_at TIMESTAMP WITHOUT TIME ZONE NULL,
                    updated_at TIMESTAMP WITHOUT TIME ZONE NULL
                )
            SQL);
            DB::statement('CREATE INDEX IF NOT EXISTS busca_ativa_processo_status_idx ON busca_ativa.processo (status, aberto_em DESC)');
        }

        if (! Schema::hasTable('busca_ativa.processo_evento')) {
            DB::statement(<<<'SQL'
                CREATE TABLE busca_ativa.processo_evento (
                    id BIGSERIAL PRIMARY KEY,
                    processo_id BIGINT NOT NULL REFERENCES busca_ativa.processo(id) ON DELETE CASCADE,
                    tipo VARCHAR(40) NOT NULL,
                    descricao TEXT NULL,
                    canal VARCHAR(30) NULL,
                    usuario_id INTEGER NULL,
                    payload JSONB NOT NULL DEFAULT '{}'::jsonb,
                    created_at TIMESTAMP WITHOUT TIME ZONE NULL
                )
            SQL);
        }

        if (! Schema::hasTable('busca_ativa.api_token')) {
            DB::statement(<<<'SQL'
                CREATE TABLE busca_ativa.api_token (
                    id BIGSERIAL PRIMARY KEY,
                    ref_cod_instituicao INTEGER NOT NULL,
                    nome VARCHAR(120) NOT NULL,
                    token_hash VARCHAR(64) NOT NULL,
                    token_prefix VARCHAR(12) NOT NULL,
                    escopos JSONB NOT NULL DEFAULT '["familia.read","familia.write"]'::jsonb,
                    ativo BOOLEAN NOT NULL DEFAULT TRUE,
                    ultimo_uso_em TIMESTAMP WITHOUT TIME ZONE NULL,
                    expira_em TIMESTAMP WITHOUT TIME ZONE NULL,
                    created_by INTEGER NULL,
                    created_at TIMESTAMP WITHOUT TIME ZONE NULL,
                    updated_at TIMESTAMP WITHOUT TIME ZONE NULL,
                    CONSTRAINT busca_ativa_api_token_hash_uk UNIQUE (token_hash)
                )
            SQL);
        }

        if (! Schema::hasTable('busca_ativa.familia_acompanhamento')) {
            DB::statement(<<<'SQL'
                CREATE TABLE busca_ativa.familia_acompanhamento (
                    id BIGSERIAL PRIMARY KEY,
                    processo_id BIGINT NOT NULL REFERENCES busca_ativa.processo(id) ON DELETE CASCADE,
                    ref_cod_aluno INTEGER NOT NULL,
                    canal VARCHAR(30) NOT NULL DEFAULT 'api',
                    status_contato VARCHAR(30) NOT NULL DEFAULT 'pendente',
                    retorno_familia TEXT NULL,
                    compromisso_retorno_em DATE NULL,
                    registrado_por VARCHAR(120) NULL,
                    payload JSONB NOT NULL DEFAULT '{}'::jsonb,
                    created_at TIMESTAMP WITHOUT TIME ZONE NULL,
                    updated_at TIMESTAMP WITHOUT TIME ZONE NULL
                )
            SQL);
        }
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS busca_ativa.familia_acompanhamento CASCADE');
        DB::statement('DROP TABLE IF EXISTS busca_ativa.api_token CASCADE');
        DB::statement('DROP TABLE IF EXISTS busca_ativa.processo_evento CASCADE');
        DB::statement('DROP TABLE IF EXISTS busca_ativa.processo CASCADE');
        DB::statement('DROP TABLE IF EXISTS busca_ativa.alerta CASCADE');
        DB::statement('DROP TABLE IF EXISTS busca_ativa.configuracao CASCADE');
        DB::statement('DROP SCHEMA IF EXISTS busca_ativa CASCADE');
    }
};
