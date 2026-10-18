# GestãoArq

Sistema de gestão para escritórios de arquitetura e interiores: orçamentos pela hora técnica, projetos com tarefas e prazos automáticos, financeiro e painel de indicadores.

Feito em **PHP 8.2+** puro, sem framework e sem Composer. Roda com **MySQL** na hospedagem ou com **SQLite** no seu computador.

> "GestãoArq" é um nome provisório. Para trocar, mude `app_name` em `app/config.php`.

## O que tem nesta versão

| Módulo | O que faz |
|---|---|
| **Painel** | Faturamento, a receber e a pagar do mês, margem × meta, alertas de contas e tarefas vencidas, índice de saúde do escritório, fluxo de caixa dos últimos 6 meses, orçamentos em aberto, atividade recente e primeiros passos |
| **Clientes** | Cadastro de clientes e prospectos, com busca e histórico de orçamentos, projetos e faturamento |
| **Precificação** | Custos fixos e variáveis, salários da equipe, **hora técnica** calculada sozinha, parâmetros (impostos, lucro, reserva, margem de segurança, honorário por m²) e templates de projeto com etapas, tarefas e horas |
| **Orçamentos** | Criados a partir de um template, com o escopo editável, cálculo do preço, valor manual opcional, parcelas, proposta para imprimir ou salvar em PDF e status (rascunho → enviado → aprovado ou recusado → arquivado) |
| **Projetos** | Criados a partir do orçamento aprovado: as tarefas são herdadas, os prazos de cada etapa são calculados em sequência e as parcelas vão para o Financeiro. Também mostram progresso e resultado projetado |
| **Tarefas** | Visão geral com filtros (atrasadas, vencem hoje, próximos 7 dias, minhas, concluídas), responsável, prazo, prioridade e status |
| **Financeiro** | Entradas e saídas, situação (atrasado, vence hoje, a vencer, realizado), baixa, desfazer baixa, duplicar para o mês seguinte, competência, categoria, forma de pagamento, projeto filtrado pelo cliente, saldo em caixa e despesas por categoria |
| **Documentos** | Contrato de prestação de serviços, aditivo, ata de reunião, termo de aprovação e termo de encerramento, gerados com os dados do escritório, do cliente, do projeto e do orçamento (escopo por etapa, valor por extenso, parcelas, prazo). Texto editável, aviso de trechos `[a preencher]`, impressão e PDF, **editor de cláusulas** por tipo com restauração do original |
| **Assinatura eletrônica** | Link para o cliente ler e assinar sem criar conta. Registra nome, CPF/CNPJ, data, hora, IP, navegador e o **código SHA-256** do texto, e confere se o documento continua igual ao que foi assinado. Também registra assinaturas feitas em papel |
| **Coleta de dados** | Link de uso único para o cliente completar nome, CPF/CNPJ, contato e endereço que entram no contrato. Dá para enviar pelo WhatsApp |
| **Briefings** | 4 modelos prontos (pré-briefing, residencial completo, interiores, comercial), editor de perguntas com 8 tipos de resposta, seções, obrigatórias, reordenação e duplicação de modelos. O cliente responde por link, pode salvar e continuar depois. O escritório recebe o relatório (imprimível) e pode reabrir para ajustes |
| **Equipe** | Membros, perfis, **permissões em 3 níveis** por módulo (sem acesso, ver ou editar) e dados do escritório usados nos documentos |

### Regras principais

- **Hora técnica** = (custos mensais + salários da equipe ativa) ÷ horas mensais da equipe ativa.
- **Preço do orçamento** = horas × (1 + margem de segurança) × hora técnica × (1 + lucro) × (1 + reserva) ÷ (1 − impostos).
- **Ao aprovar um orçamento**, o prospecto vira cliente.
- **Ao criar o projeto a partir do orçamento**, cada etapa vence `duração` dias depois da anterior, começando na data de início.
- **Perfis:** Sênior e Administrativo têm acesso total; Gestor de Projetos tem tudo menos Financeiro; Colaborador segue a tela de Permissões (por padrão só vê Painel e Projetos). Quem só tem "Ver" em Projetos ainda pode atualizar as tarefas em que é responsável.
- **Índice de saúde** = média de três notas:
  - Financeiro: contas em aberto que não estão atrasadas;
  - Operacional: tarefas em aberto que estão no prazo;
  - Comercial: taxa de aprovação dos orçamentos nos últimos 6 meses.

- **Documentos:** um documento assinado não pode ser editado nem excluído. Se o texto mudar depois de enviado, ele volta para rascunho e o link antigo para de funcionar. A assinatura só é aceita para a versão exata do texto que o cliente leu.
- **Briefings:** o briefing guarda uma cópia das perguntas no envio, então mudar o modelo depois não altera o que o cliente já recebeu. Respostas fora das opções oferecidas são descartadas.

> Os textos padrão de contrato e termos são um ponto de partida. Revise-os com a assessoria jurídica do escritório antes de usar.

### Segurança

Senhas com `password_hash`, proteção CSRF em todos os formulários, consultas preparadas (PDO), sessão encerrada após 2 h sem uso, dados isolados por escritório e cookies `HttpOnly` / `SameSite`. Os links enviados ao cliente usam tokens aleatórios de 192 bits, não são indexados por buscadores e só dão acesso ao documento, briefing ou cadastro daquele link.

## Atualizar para uma versão nova

Depois de baixar os arquivos novos, rode:

```bash
php migrate.php
```

Ele aplica só as mudanças de banco que faltam, sem apagar dados. No Windows, o `iniciar.bat` já faz isso sozinho.

## Rodar no seu computador (Windows)

1. Instale o PHP 8.2 ou mais novo (por exemplo, `winget install PHP.PHP.8.3`).
2. Dê dois cliques em **`iniciar.bat`**.
3. Na primeira vez, ele pergunta o nome do escritório, o seu nome, o e-mail e a senha, cria o banco SQLite em `storage/` e abre `http://localhost:8000`.

O `php-local.bat` liga as extensões `pdo_sqlite` e `mbstring` só para o sistema, sem mexer no `php.ini`.

### Linux / macOS

```bash
cp app/config.example.php app/config.php
php install.php --escritorio="Meu Escritório" --nome="Seu Nome" --email=voce@exemplo.com --senha=suaSenha --exemplo
php -S localhost:8000 -t public public/index.php
```

`--exemplo` já cria alguns custos de exemplo. Um template de projeto residencial é sempre criado.

## Publicar numa hospedagem (MySQL)

1. Crie um banco MySQL no painel da hospedagem.
2. Envie os arquivos e aponte o domínio para a pasta **`public/`**. As pastas `app/`, `database/` e `storage/` não podem ficar acessíveis pela web.
3. Copie `app/config.example.php` para `app/config.php`, mude `db_driver` para `'mysql'` e preencha host, banco, usuário e senha.
4. Rode o `install.php` pelo terminal (SSH) da hospedagem.
5. O `.htaccess` em `public/` já cuida das URLs no Apache. No Nginx, use `try_files $uri /index.php?$query_string;`.

## Estrutura

```
app/
  bootstrap.php      carrega a configuração e as funções base
  config.example.php modelo de configuração
  db.php             conexão PDO e funções de consulta
  auth.php           login, sessão, perfis e permissões
  helpers.php        formatação, CSRF, views, mensagens
  pricing.php        hora técnica e cálculo do orçamento
  relatorios.php     séries mensais e índice de saúde
  documentos.php     campos automáticos, valor por extenso, formatação
  seeds.php          modelos padrão de documentos e briefings
  migrate.php        aplica as migrações do banco
  controllers/       um arquivo por módulo
  views/             telas em PHP
database/schema.php  estrutura base (serve para MySQL e SQLite)
database/migrations.php  mudanças de banco de cada versão
migrate.php          atualiza o banco pelo terminal
public/              raiz pública: index.php (rotas), .htaccess, assets/
install.php          instalação pelo terminal
iniciar.bat          roda no Windows com um clique
```

## Próximos módulos sugeridos

- CRM com funil de leads, agenda e cronograma em calendário
- Fotos de referência nas opções do briefing
- Importação de extrato OFX e conciliação bancária
- Relatórios por cliente e por centro de custo, e DRE
- Portal do cliente, com acesso por código enviado por e-mail
- Verificação em dois passos por e-mail
