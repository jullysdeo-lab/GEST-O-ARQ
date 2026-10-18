# Mapa funcional do Sistema COP

Levantamento feito em 17/10/2026 navegando pelo sistema logado (conta Juliana Souza, perfil Sênior, plano Gestão anual) e lendo o PDF "Avisos — COP". Só foi observado o que aparece na tela: nada foi alterado, e nenhum código ou API do COP foi copiado. O objetivo é servir de **especificação funcional** para um sistema próprio, com código seu.

---

## 1. Visão geral da arquitetura (o que dá para ver de fora)

- **Domínios:** `cop.arq.br` (site de vendas) e `sistema.cop.arq.br` (aplicação).
- **Estilo da aplicação:** páginas renderizadas no servidor (URLs `.php`), cada módulo numa pasta (`/financeiro/`, `/projetos/`...). Algumas telas carregam dados depois de abrir ("Carregando financeiro..."), ou seja, usam chamadas assíncronas para preencher gráficos e totais.
- **Layout fixo:** menu lateral permanente (recolhível), barra superior com busca global, alertas, notificações (sino), modo escuro, sair e avatar. Dentro de cada módulo há abas próprias.
- **Multi-escritório (multi-tenant):** cada conta é um escritório, com equipe, plano e configurações próprios.
- **IA:** recursos com a marca "COPi" usam a Anthropic (Claude). Depois de 31/12/2026 o cliente conecta a própria chave.

### Mapa de rotas observado

| Área | Rota |
|---|---|
| Central de Análise (início) | `/central` |
| Escritório | `/escritorio/` → `dashboard.php`, CRM, Comunicação, Atividades, Padrões, Perfil |
| Raio-X do escritório | `/escritorio/raio-x.php` |
| Equipe / Pessoas | `/escritorio/equipe.php` (Membros, Permissões, Ausências) |
| LGPD / Privacidade | `/escritorio/lgpd.php` |
| Projetos | `/projetos/` (Dashboard, Projetos, Análise, Portal do Cliente, Configurações, Tarefas, Ambientes, Cronograma) |
| Tarefas | `/projetos/tarefas.php?status=Atrasado` · `?tipo=Administrativo` |
| Financeiro | `/financeiro/` (Dashboard, Fluxo de Caixa, Entradas, Saídas, Bancos, Relatórios) |
| Precificação | `/precificacao/` · `?pane=orcamentos` · `config.php` |
| Gerador de Propostas | `/geradordeproposta/?from=precificacao` |
| Briefings | `/briefings/` |
| Documentos | `/documentos/` |
| Obras (add-on) | `/obras/` |
| Planos / upsell | `/sistema/planos.php?recurso=marketing` |
| Avisos, Aulas, Manual | `/avisos/`, `/aulas.php`, `/primeiros-passos` |

---

## 2. Módulos e funcionalidades

### 2.1 Central de Análise (tela inicial)
Painel com widgets que dá para arrastar, fechar e reordenar (ícones ⠿ e ×). Filtro de período: Semana, Mês, Trimestre ou Ano.
- **Meu foco hoje:** tarefas de Hoje, Atrasadas e Urgentes.
- **Alertas:** tarefas vencidas e contas a pagar e a receber próximas ou atrasadas.
- **Administrativo:** tarefas internas.
- **Copiloto (resumo automático):** pontos de atenção e **Índice de Saúde** de 0 a 100, com classificação (ex.: 64, "Regular"). É a combinação de três notas:
  - Financeiro (caixa e margem)
  - Operacional (projetos e obras)
  - Comercial (funil e CRM)
- **KPIs:** faturamento do mês, a receber, a pagar (com valor em atraso), margem operacional × meta (30%).
- **Gráficos:** fluxo de caixa receita × despesa dos últimos 6 meses; projetos por situação; obras (avanço físico × prazo); top clientes por faturamento.
- **Atividade recente:** log de ações da equipe ("editou parcela", "gerou resumo IA"...).
- **Cronograma** (calendário ou lista) e **Agenda**.
- **Raio-X:** questionário de diagnóstico do escritório; a IA lê as respostas e indica os módulos que ajudam.

### 2.2 Escritório
- **Dashboard:** leads ativos, valor em pipeline, em negociação, "Atenção hoje", horas da equipe e tarefas concluídas por pessoa.
- **Primeira jornada (onboarding em 6 passos com % de conclusão):**
  1. Configurar a precificação
  2. Criar o primeiro orçamento
  3. Aprovar o orçamento
  4. Enviar link de coleta ao cliente
  5. Criar o projeto a partir do orçamento
  6. Gerar e assinar o contrato
- **CRM:** leads, funil e "leads frios".
- **Comunicação, Atividades, Padrões, Perfil.**

### 2.3 Projetos
- Lista em Card ou Lista, filtro por ano, **Visão por Cliente** ou **Visão por Projeto**, filtro por datas de entrega.
- Colunas da lista: status (Planejamento, Em andamento, Concluído...), código (ex.: `26002`, ano mais sequencial), cliente, origem ("↳ do orçamento ORC-2026-001"), fase atual, % de progresso, tarefas concluídas/total, atrasadas, início, entrega, equipe, horas.
- **Fases padrão (template):** 1. Início do Projeto → 2. Concepção → 3. Projeto Legal → 4. Projeto Executivo → 5. Encerramento.
- **Tarefas:** agrupadas por projeto e fase, com responsável, prazo, prioridade, status, checklist e horas. Filtros por status ou tipo.
- **Exibir TV:** painel público, sem login, com 5 colunas (Atrasadas, Urgentes, Amanhã, Próximos 7 dias, 8 a 30 dias). Atualiza a cada 5 minutos.
- **Ambientes, Cronograma, Análise** (comparativo de horas e rentabilidade com o COPi).
- **Portal do Cliente:** o cliente entra com código por e-mail, sem senha. Há 3 níveis de visão (só publicações, resumido, completo) e aprovações formais imutáveis, registradas com data, hora e identificação.

### 2.4 Financeiro
- **Dashboard:** saldo em caixa, a receber, recebido, a pagar, em atraso; evolução mensal; despesas por categoria; filtro De/Até.
- **Entradas e Saídas:** os antigos "A Receber" e "A Pagar". Cada lançamento tem cliente, projeto, categoria, centro de custo, forma de pagamento, competência, vencimento, anexos e comprovante. Status: aguardando, vence hoje, a vencer, atrasado, realizado. Ações: duplicar e "voltar para aguardando" (desfaz uma baixa).
- **Fluxo de Caixa.**
- **Bancos:** contas, saldo inicial com data, transferências entre contas, importação de **OFX** com **conciliação** (o sistema sugere o casamento extrato × lançamento), extrato diário com exportação CSV/PDF.
- **Relatórios:** por cliente, por centro de custo, DRE em seções e **Resultado projetado** (lucro esperado = realizado + a receber − a pagar).
- **Integração:** as parcelas do contrato nascem já vinculadas ao projeto.

### 2.5 Precificação (núcleo de cálculo)
- **Dashboard:** total de orçamentos, rascunhos, enviados, aprovados, valor aprovado, gráficos por mês e por status.
- **Orçamentos:** lista ou Kanban, filtro por ano e arquivados, "Novo Orçamento" e "Orçamento Avulso". O card mostra mês de referência, origem do cliente e data de aprovação. Numeração `ORC-AAAA-NNN`.
- **Configurações permanentes:**
  - **Custos:** Equipe (nome, cargo, horas/dia, dias/mês, total h/mês, ativo), Custos Fixos, Custos Variáveis, Impostos, Centros de Custo, Hora técnica, Parâmetros.
  - **Projetos:** Ambientes, Templates, Complementares.
  - **Cargos:** Colaborador, Financeiro, Jurídico, Administrativo, Gestor de Projetos, Sênior, Estagiário, Arquiteto, Designer, e cargos personalizados.
- **Fórmula da hora técnica:**
  - `custo_hora = custo_fixo_total ÷ horas_mensais_equipe_ativa`. Exemplo da tela: R$ 16.680 ÷ 352 h = **R$ 47,39/h**.
  - Versão ponderada: o salário de cada pessoa gera o custo/hora dela (planos Pro e Premium).
- **Parâmetros de cálculo:** reserva padrão (%), margem de segurança nas horas (%), honorário base por m² (R$) e CUB/m² do estado (só referência para a tabela do CAU).
- **Calculadora de honorários por m²:** abre a partir do orçamento e devolve o valor para ele.
- **Cronômetro** na barra superior para apontar horas.
- **Gerador de Propostas:** transforma o orçamento em proposta comercial.

### 2.6 Briefings
- **Pré-Briefings:** Construção, Reforma, Interiores, Comercial.
- **Residenciais:** Briefing Residencial, Iluminação, Paisagismo.
- **Comerciais:** Comercial Completo, Escritório, Restaurante, Salão de Beleza, Loja de Roupas.
- **Especializados:** Cozinha Industrial e outros.
- **Editor de perguntas** por tipo, com foto de referência em cada opção de resposta. O cliente responde por link, e o sistema gera um relatório e salva o PDF em Documentos.
- **Briefing dinâmico com IA.** Pode ser criado a partir de um lead ou de um orçamento, já com os dados do cliente preenchidos.

### 2.7 Documentos
- **Tipos:** Contrato de serviços (Contrato blindado), Aditivo contratual, Ata de reunião, Termo de aprovação, Termo de encerramento.
- **Assistente em 4 passos:** Tipo → Cliente → Dados → Gerar (pré-visualizar, salvar, baixar PDF).
- "Solicitar dados por link": o cliente preenche os próprios dados.
- **Editor de cláusulas** por tipo, com opção de restaurar o modelo original. Assinatura digital (Pro).

### 2.8 Pessoas / Equipe
- **Membros:** nome, perfil, vínculo, especialidade, disponibilidade, status, custo/hora, último acesso, Dados de RH; convite por e-mail.
- **Ausências.**
- **Permissões:** para cada pessoa e módulo, 3 níveis (Sem acesso, Ver, Editar). Áreas sensíveis têm linha própria: templates, portal, dados bancários, relatórios e DRE, clientes, configurações da precificação, modelos jurídicos, editor de perguntas.
- **Perfis fixos:** Sênior e Administrativo têm acesso total; Gestor de Projetos não acessa o Financeiro; a Agenda é aberta a todos.

### 2.9 Obras (add-on) e Marketing (Premium)
- **Obras:** diário de obras (até 20 fotos com legenda por relatório, PDF), categorias, status e tipos de tarefa reordenáveis por arraste, avanço físico × prazo.
- **Marketing:** geração de pauta e plano de conteúdo por IA.

### 2.10 Recursos transversais
- Busca global, notificações (contas atrasadas, tarefas vencidas, leads frios), modo escuro.
- **Segurança:** verificação em dois passos por código de 6 dígitos por e-mail quando o aparelho é novo; o aparelho fica confiável por 90 dias; a sessão expira após 2 h sem uso.
- Log de atividades, LGPD, aulas, manual, suporte por WhatsApp, chat de ajuda com IA (COPi Ajuda).
- Planos (Gestão, Gestão Pro, Premium; mensal, anual ou fundador) liberam recursos; os bloqueados aparecem com selo e levam à página de upgrade.

---

## 3. Regras de negócio e fluxo principal

O fluxo que conecta os módulos é a "propagação de informações":

```
Lead (CRM) ──► Orçamento (Precificação) ──aprovar──► Prospecto vira Cliente
                    │
                    ├──► Briefing (dados do cliente pré-preenchidos)
                    │
                    └──► Projeto (etapas/tarefas do escopo orçado viram tarefas reais;
                    │             prazos calculados em sequência pela duração de cada etapa no template)
                    │
                    └──► Contrato (Documentos) ──assinar──► Parcelas no Financeiro vinculadas ao projeto
                                                               │
                                                               └──► Resultado projetado, DRE, COPi (rentabilidade)
```

Outras regras observadas:
- **Status de vencimento:** atrasado (vencimento < hoje e não realizado), vence hoje, a vencer.
- **Ao criar uma entrada,** a lista de projetos é filtrada pelo cliente escolhido.
- **Progresso do projeto** = tarefas concluídas ÷ total.
- **Índice de saúde** = combinação das notas Financeiro, Operacional e Comercial.
- **O COPi exige** um orçamento aprovado vinculado ao projeto. Limite de 50 análises por mês por escritório.

---

## 4. Proposta de modelo de dados para o seu sistema

```
escritorio(id, nome, plano, ciclo, chave_ia, ...)
usuario(id, escritorio_id, nome, email, perfil, cargo, custo_hora, horas_dia, dias_mes, ativo, ultimo_acesso)
permissao(usuario_id, modulo, nivel[sem|ver|editar])
dispositivo_confiavel(usuario_id, hash, valido_ate)

lead(id, escritorio_id, nome, origem, etapa_funil, valor, ultimo_contato)
cliente(id, escritorio_id, nome, doc, email, telefone, origem, portal_ativo, portal_nivel)

config_custo(escritorio_id, tipo[fixo|variavel|imposto], nome, valor, centro_custo_id)
centro_custo(id, escritorio_id, nome)
parametros_orcamento(escritorio_id, reserva_pct, margem_horas_pct, honorario_m2, cub_m2)
template_projeto(id, escritorio_id, nome) → template_etapa(id, template_id, ordem, nome, duracao_dias)
                                           → template_tarefa(id, etapa_id, nome, horas_previstas)
ambiente(id, escritorio_id, nome)

orcamento(id, escritorio_id, numero 'ORC-AAAA-NNN', cliente_id|lead_id, status[rascunho|enviado|aprovado|arquivado],
          mes_ref, area_m2, valor_total, aprovado_em)
orcamento_item(id, orcamento_id, etapa, tarefa, horas, usuario_id, custo_hora)

projeto(id, escritorio_id, codigo, cliente_id, orcamento_id, status, fase_atual, inicio, entrega)
tarefa(id, projeto_id, etapa, nome, responsavel_id, prazo, prioridade, status, tipo, horas_previstas)
checklist_item(id, tarefa_id, texto, feito)
apontamento_hora(id, usuario_id, tarefa_id, inicio, fim, minutos)

briefing_tipo(id, escritorio_id, categoria, nome) → pergunta(id, tipo_id, ordem, texto, tipo_resposta)
                                                 → opcao(id, pergunta_id, texto, foto_url)
briefing(id, cliente_id, tipo_id, token_link, status) → resposta(briefing_id, pergunta_id, valor)

documento_modelo(id, escritorio_id, tipo, clausulas_texto, original_texto)
documento(id, cliente_id, projeto_id, tipo, dados_json, pdf_url, assinado_em)

lancamento(id, escritorio_id, tipo[entrada|saida], cliente_id, projeto_id, categoria, centro_custo_id,
           descricao, valor, vencimento, competencia, forma_pgto, status, realizado_em, conta_id, comprovante_url)
conta_bancaria(id, escritorio_id, nome, saldo_inicial, data_saldo_inicial)
extrato_linha(id, conta_id, data, valor, descricao, fitid, lancamento_id NULL)   -- conciliação OFX
transferencia(id, conta_origem, conta_destino, valor, data)

portal_publicacao(id, projeto_id, tipo, conteudo, visivel)
aprovacao(id, projeto_id, descricao, status, decidido_em, ip, user_agent)        -- imutável
atividade_log(id, escritorio_id, usuario_id, acao, entidade, entidade_id, criado_em)
obra(id, projeto_id, ...) → diario_obra(id, obra_id, data, texto) → foto(id, diario_id, url, legenda)
```

---

## 5. Sugestão de ordem de desenvolvimento (MVP → completo)

1. Autenticação, escritório, equipe e permissões em 3 níveis.
2. Precificação: custos, equipe, hora técnica, templates e orçamento (núcleo de valor).
3. Aprovação do orçamento → cliente + projeto com tarefas herdadas e prazos automáticos.
4. Financeiro básico (entradas e saídas, status de vencimento, fluxo de caixa) + parcelas geradas do contrato.
5. Documentos com cláusulas editáveis e PDF; briefings por link.
6. Central de Análise (KPIs, alertas, índice de saúde) e notificações.
7. Bancos/OFX com conciliação, relatórios e DRE, Resultado projetado.
8. Portal do Cliente, painel de TV, IA (resumos, orçamento por IA, briefing dinâmico).
9. Add-ons: Obras, Marketing, Reuniões.

**Pontos a diferenciar no seu produto:** o COP é muito rico em configuração. Um sistema próprio pode se destacar com um onboarding mais curto, um app mobile nativo (o deles ainda está "em breve") e integrações (WhatsApp, bancos via Open Finance em vez de OFX manual).
