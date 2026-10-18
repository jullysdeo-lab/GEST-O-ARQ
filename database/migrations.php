<?php
// Migrações aplicadas em ordem depois da estrutura base (schema.php).
// Nunca altere uma migração já publicada: crie uma nova versão.
return [
    // Bloco 1: dados do cliente para contratos, Documentos e Briefings.
    2 => [
        'ALTER TABLE cliente ADD COLUMN endereco VARCHAR(255)',
        'ALTER TABLE cliente ADD COLUMN token_dados VARCHAR(64)',
        'ALTER TABLE cliente ADD COLUMN dados_atualizados_em DATETIME',

        "CREATE TABLE documento_modelo (
            id {PK},
            escritorio_id INT NOT NULL,
            tipo VARCHAR(30) NOT NULL,
            conteudo TEXT NOT NULL,
            atualizado_em DATETIME NOT NULL,
            UNIQUE (escritorio_id, tipo),
            FOREIGN KEY (escritorio_id) REFERENCES escritorio(id)
        )",

        "CREATE TABLE documento (
            id {PK},
            escritorio_id INT NOT NULL,
            tipo VARCHAR(30) NOT NULL,
            titulo VARCHAR(200) NOT NULL,
            cliente_id INT NOT NULL,
            projeto_id INT,
            orcamento_id INT,
            conteudo TEXT NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'rascunho',
            token VARCHAR(64),
            enviado_em DATETIME,
            assinado_em DATETIME,
            assinado_nome VARCHAR(150),
            assinado_documento VARCHAR(30),
            assinado_ip VARCHAR(45),
            assinado_agente VARCHAR(255),
            assinado_hash VARCHAR(64),
            criado_por INT,
            criado_em DATETIME NOT NULL,
            FOREIGN KEY (escritorio_id) REFERENCES escritorio(id),
            FOREIGN KEY (cliente_id) REFERENCES cliente(id),
            FOREIGN KEY (projeto_id) REFERENCES projeto(id) ON DELETE SET NULL,
            FOREIGN KEY (orcamento_id) REFERENCES orcamento(id) ON DELETE SET NULL
        )",

        "CREATE TABLE briefing_modelo (
            id {PK},
            escritorio_id INT NOT NULL,
            nome VARCHAR(120) NOT NULL,
            categoria VARCHAR(60),
            descricao VARCHAR(255),
            ativo INT NOT NULL DEFAULT 1,
            FOREIGN KEY (escritorio_id) REFERENCES escritorio(id)
        )",

        "CREATE TABLE briefing_pergunta (
            id {PK},
            modelo_id INT NOT NULL,
            ordem INT NOT NULL DEFAULT 1,
            secao VARCHAR(80),
            texto VARCHAR(255) NOT NULL,
            tipo VARCHAR(20) NOT NULL DEFAULT 'texto',
            opcoes TEXT,
            obrigatoria INT NOT NULL DEFAULT 0,
            FOREIGN KEY (modelo_id) REFERENCES briefing_modelo(id) ON DELETE CASCADE
        )",

        "CREATE TABLE briefing (
            id {PK},
            escritorio_id INT NOT NULL,
            modelo_id INT,
            titulo VARCHAR(160) NOT NULL,
            cliente_id INT NOT NULL,
            projeto_id INT,
            orcamento_id INT,
            perguntas_json TEXT NOT NULL,
            respostas_json TEXT,
            status VARCHAR(20) NOT NULL DEFAULT 'aguardando',
            token VARCHAR(64) NOT NULL,
            enviado_em DATETIME NOT NULL,
            respondido_em DATETIME,
            criado_por INT,
            FOREIGN KEY (escritorio_id) REFERENCES escritorio(id),
            FOREIGN KEY (cliente_id) REFERENCES cliente(id),
            FOREIGN KEY (modelo_id) REFERENCES briefing_modelo(id) ON DELETE SET NULL,
            FOREIGN KEY (projeto_id) REFERENCES projeto(id) ON DELETE SET NULL,
            FOREIGN KEY (orcamento_id) REFERENCES orcamento(id) ON DELETE SET NULL
        )",

        'CREATE UNIQUE INDEX idx_documento_token ON documento (token)',
        'CREATE UNIQUE INDEX idx_briefing_token ON briefing (token)',
        'CREATE UNIQUE INDEX idx_cliente_token ON cliente (token_dados)',

        // Modelos padrão para os escritórios que já existem.
        function () {
            require_once APP_ROOT . '/app/seeds.php';
            foreach (all('SELECT id FROM escritorio') as $e) {
                seed_documentos((int) $e['id']);
                seed_briefings((int) $e['id']);
            }
        },
    ],

    // Bloco 2: CRM (funil e histórico de contatos), Agenda e assinatura de calendário.
    3 => [
        'ALTER TABLE cliente ADD COLUMN funil_etapa VARCHAR(20)',
        'ALTER TABLE cliente ADD COLUMN valor_estimado DECIMAL(12,2)',
        'ALTER TABLE cliente ADD COLUMN responsavel_id INT',
        'ALTER TABLE cliente ADD COLUMN temperatura VARCHAR(10)',
        'ALTER TABLE cliente ADD COLUMN tipo_projeto VARCHAR(60)',
        'ALTER TABLE cliente ADD COLUMN proximo_contato DATE',
        'ALTER TABLE cliente ADD COLUMN ultimo_contato DATETIME',
        'ALTER TABLE cliente ADD COLUMN motivo_perda VARCHAR(160)',
        "UPDATE cliente SET funil_etapa = CASE WHEN tipo = 'cliente' THEN 'ganho' ELSE 'novo' END",
        "UPDATE cliente SET temperatura = 'morno'",
        'ALTER TABLE usuario ADD COLUMN token_agenda VARCHAR(64)',

        "CREATE TABLE interacao (
            id {PK},
            escritorio_id INT NOT NULL,
            cliente_id INT NOT NULL,
            usuario_id INT,
            tipo VARCHAR(20) NOT NULL DEFAULT 'nota',
            descricao TEXT NOT NULL,
            data DATETIME NOT NULL,
            FOREIGN KEY (escritorio_id) REFERENCES escritorio(id),
            FOREIGN KEY (cliente_id) REFERENCES cliente(id) ON DELETE CASCADE,
            FOREIGN KEY (usuario_id) REFERENCES usuario(id) ON DELETE SET NULL
        )",

        "CREATE TABLE evento (
            id {PK},
            escritorio_id INT NOT NULL,
            titulo VARCHAR(160) NOT NULL,
            tipo VARCHAR(20) NOT NULL DEFAULT 'reuniao',
            inicio DATETIME NOT NULL,
            fim DATETIME NOT NULL,
            dia_todo INT NOT NULL DEFAULT 0,
            local VARCHAR(200),
            descricao TEXT,
            cliente_id INT,
            projeto_id INT,
            criado_por INT,
            criado_em DATETIME NOT NULL,
            FOREIGN KEY (escritorio_id) REFERENCES escritorio(id),
            FOREIGN KEY (cliente_id) REFERENCES cliente(id) ON DELETE SET NULL,
            FOREIGN KEY (projeto_id) REFERENCES projeto(id) ON DELETE SET NULL,
            FOREIGN KEY (criado_por) REFERENCES usuario(id) ON DELETE SET NULL
        )",

        "CREATE TABLE evento_participante (
            id {PK},
            evento_id INT NOT NULL,
            usuario_id INT NOT NULL,
            UNIQUE (evento_id, usuario_id),
            FOREIGN KEY (evento_id) REFERENCES evento(id) ON DELETE CASCADE,
            FOREIGN KEY (usuario_id) REFERENCES usuario(id) ON DELETE CASCADE
        )",

        'CREATE INDEX idx_evento_inicio ON evento (escritorio_id, inicio)',
        'CREATE INDEX idx_interacao_cliente ON interacao (cliente_id, data)',
        'CREATE UNIQUE INDEX idx_usuario_token_agenda ON usuario (token_agenda)',
    ],
];
