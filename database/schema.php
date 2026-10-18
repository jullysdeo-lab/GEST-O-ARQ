<?php
// Estrutura do banco. {PK} vira a chave primária certa para MySQL ou SQLite.
return [
    "CREATE TABLE escritorio (
        id {PK},
        nome VARCHAR(150) NOT NULL,
        criado_em DATETIME NOT NULL
    )",

    "CREATE TABLE usuario (
        id {PK},
        escritorio_id INT NOT NULL,
        nome VARCHAR(150) NOT NULL,
        email VARCHAR(190) NOT NULL UNIQUE,
        senha_hash VARCHAR(255) NOT NULL,
        perfil VARCHAR(30) NOT NULL DEFAULT 'colaborador',
        cargo VARCHAR(80),
        horas_dia DECIMAL(5,2) NOT NULL DEFAULT 8,
        dias_mes INT NOT NULL DEFAULT 22,
        salario DECIMAL(12,2) NOT NULL DEFAULT 0,
        ativo INT NOT NULL DEFAULT 1,
        ultimo_acesso DATETIME,
        criado_em DATETIME NOT NULL,
        FOREIGN KEY (escritorio_id) REFERENCES escritorio(id)
    )",

    "CREATE TABLE permissao (
        id {PK},
        usuario_id INT NOT NULL,
        modulo VARCHAR(30) NOT NULL,
        nivel INT NOT NULL DEFAULT 0,
        UNIQUE (usuario_id, modulo),
        FOREIGN KEY (usuario_id) REFERENCES usuario(id) ON DELETE CASCADE
    )",

    "CREATE TABLE config (
        id {PK},
        escritorio_id INT NOT NULL,
        chave VARCHAR(60) NOT NULL,
        valor VARCHAR(255),
        UNIQUE (escritorio_id, chave),
        FOREIGN KEY (escritorio_id) REFERENCES escritorio(id)
    )",

    "CREATE TABLE cliente (
        id {PK},
        escritorio_id INT NOT NULL,
        nome VARCHAR(150) NOT NULL,
        tipo VARCHAR(20) NOT NULL DEFAULT 'prospecto',
        email VARCHAR(190),
        telefone VARCHAR(40),
        documento VARCHAR(30),
        origem VARCHAR(60),
        observacoes TEXT,
        criado_em DATETIME NOT NULL,
        FOREIGN KEY (escritorio_id) REFERENCES escritorio(id)
    )",

    "CREATE TABLE custo (
        id {PK},
        escritorio_id INT NOT NULL,
        tipo VARCHAR(20) NOT NULL DEFAULT 'fixo',
        nome VARCHAR(120) NOT NULL,
        valor_mensal DECIMAL(12,2) NOT NULL DEFAULT 0,
        FOREIGN KEY (escritorio_id) REFERENCES escritorio(id)
    )",

    "CREATE TABLE template (
        id {PK},
        escritorio_id INT NOT NULL,
        nome VARCHAR(120) NOT NULL,
        FOREIGN KEY (escritorio_id) REFERENCES escritorio(id)
    )",

    "CREATE TABLE template_etapa (
        id {PK},
        template_id INT NOT NULL,
        ordem INT NOT NULL DEFAULT 1,
        nome VARCHAR(120) NOT NULL,
        duracao_dias INT NOT NULL DEFAULT 7,
        FOREIGN KEY (template_id) REFERENCES template(id) ON DELETE CASCADE
    )",

    "CREATE TABLE template_tarefa (
        id {PK},
        etapa_id INT NOT NULL,
        ordem INT NOT NULL DEFAULT 1,
        nome VARCHAR(160) NOT NULL,
        horas DECIMAL(7,2) NOT NULL DEFAULT 0,
        FOREIGN KEY (etapa_id) REFERENCES template_etapa(id) ON DELETE CASCADE
    )",

    "CREATE TABLE orcamento (
        id {PK},
        escritorio_id INT NOT NULL,
        numero VARCHAR(20) NOT NULL,
        cliente_id INT NOT NULL,
        titulo VARCHAR(160) NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'rascunho',
        area_m2 DECIMAL(10,2),
        custo_hora DECIMAL(10,2) NOT NULL DEFAULT 0,
        horas_total DECIMAL(10,2) NOT NULL DEFAULT 0,
        custo_total DECIMAL(12,2) NOT NULL DEFAULT 0,
        valor_total DECIMAL(12,2) NOT NULL DEFAULT 0,
        valor_manual DECIMAL(12,2),
        parcelas INT NOT NULL DEFAULT 1,
        observacoes TEXT,
        criado_por INT,
        criado_em DATETIME NOT NULL,
        enviado_em DATETIME,
        aprovado_em DATETIME,
        FOREIGN KEY (escritorio_id) REFERENCES escritorio(id),
        FOREIGN KEY (cliente_id) REFERENCES cliente(id)
    )",

    "CREATE TABLE orcamento_item (
        id {PK},
        orcamento_id INT NOT NULL,
        etapa_ordem INT NOT NULL DEFAULT 1,
        etapa_nome VARCHAR(120) NOT NULL,
        duracao_dias INT NOT NULL DEFAULT 7,
        tarefa_nome VARCHAR(160) NOT NULL,
        horas DECIMAL(7,2) NOT NULL DEFAULT 0,
        FOREIGN KEY (orcamento_id) REFERENCES orcamento(id) ON DELETE CASCADE
    )",

    "CREATE TABLE projeto (
        id {PK},
        escritorio_id INT NOT NULL,
        codigo VARCHAR(20) NOT NULL,
        nome VARCHAR(160) NOT NULL,
        cliente_id INT NOT NULL,
        orcamento_id INT,
        status VARCHAR(20) NOT NULL DEFAULT 'planejamento',
        inicio DATE,
        entrega DATE,
        criado_em DATETIME NOT NULL,
        FOREIGN KEY (escritorio_id) REFERENCES escritorio(id),
        FOREIGN KEY (cliente_id) REFERENCES cliente(id),
        FOREIGN KEY (orcamento_id) REFERENCES orcamento(id)
    )",

    "CREATE TABLE tarefa (
        id {PK},
        projeto_id INT NOT NULL,
        etapa_ordem INT NOT NULL DEFAULT 1,
        etapa_nome VARCHAR(120) NOT NULL DEFAULT 'Geral',
        nome VARCHAR(160) NOT NULL,
        responsavel_id INT,
        prazo DATE,
        prioridade VARCHAR(10) NOT NULL DEFAULT 'media',
        status VARCHAR(20) NOT NULL DEFAULT 'a_fazer',
        horas_previstas DECIMAL(7,2) NOT NULL DEFAULT 0,
        concluida_em DATETIME,
        FOREIGN KEY (projeto_id) REFERENCES projeto(id) ON DELETE CASCADE,
        FOREIGN KEY (responsavel_id) REFERENCES usuario(id) ON DELETE SET NULL
    )",

    "CREATE TABLE lancamento (
        id {PK},
        escritorio_id INT NOT NULL,
        tipo VARCHAR(10) NOT NULL,
        descricao VARCHAR(200) NOT NULL,
        valor DECIMAL(12,2) NOT NULL,
        vencimento DATE NOT NULL,
        competencia VARCHAR(7),
        categoria VARCHAR(80),
        forma_pagamento VARCHAR(40),
        cliente_id INT,
        projeto_id INT,
        status VARCHAR(20) NOT NULL DEFAULT 'aguardando',
        realizado_em DATE,
        criado_em DATETIME NOT NULL,
        FOREIGN KEY (escritorio_id) REFERENCES escritorio(id),
        FOREIGN KEY (cliente_id) REFERENCES cliente(id) ON DELETE SET NULL,
        FOREIGN KEY (projeto_id) REFERENCES projeto(id) ON DELETE SET NULL
    )",

    "CREATE TABLE atividade (
        id {PK},
        escritorio_id INT NOT NULL,
        usuario_id INT,
        acao VARCHAR(255) NOT NULL,
        criado_em DATETIME NOT NULL,
        FOREIGN KEY (escritorio_id) REFERENCES escritorio(id)
    )",

    "CREATE INDEX idx_tarefa_prazo ON tarefa (projeto_id, status, prazo)",
    "CREATE INDEX idx_lanc_venc ON lancamento (escritorio_id, tipo, status, vencimento)",
];
