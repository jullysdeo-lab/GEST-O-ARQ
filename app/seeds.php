<?php
declare(strict_types=1);

/*
 * Modelos padrão criados para cada escritório.
 * Os textos de documentos são modelos de partida: revise com a assessoria jurídica do escritório.
 * Formatação: "# " título, "## " seção, "- " item de lista, **negrito**; {{campo}} é preenchido pelo sistema.
 */

const TIPOS_DOCUMENTO = [
    'contrato' => 'Contrato de prestação de serviços',
    'aditivo' => 'Aditivo contratual',
    'ata' => 'Ata de reunião',
    'termo_aprovacao' => 'Termo de aprovação',
    'termo_encerramento' => 'Termo de encerramento',
];

function modelos_documento_padrao(): array
{
    return [
        'contrato' => <<<'TXT'
# CONTRATO DE PRESTAÇÃO DE SERVIÇOS DE ARQUITETURA

**CONTRATADA:** {{escritorio.nome}}, inscrita no CNPJ/CPF sob o nº {{escritorio.cnpj}}, com endereço em {{escritorio.endereco}}, {{escritorio.cidade}}, neste ato representada por {{escritorio.responsavel}}, arquiteto(a) e urbanista registrado(a) no CAU sob o nº {{escritorio.cau}}.

**CONTRATANTE:** {{cliente.nome}}, inscrito(a) no CPF/CNPJ sob o nº {{cliente.documento}}, residente ou sediado(a) em {{cliente.endereco}}, e-mail {{cliente.email}}, telefone {{cliente.telefone}}.

As partes acima identificadas celebram o presente contrato, que se regerá pelas cláusulas a seguir.

## CLÁUSULA 1ª — DO OBJETO

O presente contrato tem por objeto a prestação de serviços técnicos de arquitetura referentes ao projeto **{{projeto.nome}}**, com área estimada de {{orcamento.area}} m², conforme a proposta {{orcamento.numero}}, que passa a fazer parte deste instrumento.

## CLÁUSULA 2ª — DO ESCOPO

Os serviços compreendem as seguintes etapas:

{{orcamento.escopo}}

Qualquer serviço não listado acima, incluindo novas versões de etapas já aprovadas, será considerado serviço adicional e dependerá de aditivo contratual.

## CLÁUSULA 3ª — DOS PRAZOS

O prazo estimado para a execução dos serviços é de {{orcamento.prazo_dias}} dias corridos, contados a partir da assinatura deste contrato e do recebimento de todas as informações necessárias para o início dos trabalhos.

Parágrafo único. Os prazos ficam suspensos enquanto estiverem pendentes aprovações, informações ou documentos de responsabilidade do CONTRATANTE, ou análises de órgãos públicos.

## CLÁUSULA 4ª — DOS HONORÁRIOS E DA FORMA DE PAGAMENTO

Pelos serviços contratados, o CONTRATANTE pagará à CONTRATADA o valor total de **{{orcamento.valor}}** ({{orcamento.valor_extenso}}), em {{orcamento.parcelas}} parcela(s) de {{orcamento.valor_parcela}}.

§1º O atraso no pagamento sujeitará o CONTRATANTE a multa de 2% (dois por cento) sobre o valor devido, juros de mora de 1% (um por cento) ao mês e correção monetária.

§2º O atraso superior a 30 (trinta) dias autoriza a CONTRATADA a suspender os serviços até a regularização, sem prejuízo dos prazos.

## CLÁUSULA 5ª — DAS OBRIGAÇÕES DA CONTRATADA

- Executar os serviços com zelo, observando as normas técnicas e a legislação aplicáveis;
- Emitir o Registro de Responsabilidade Técnica (RRT) junto ao CAU;
- Manter o CONTRATANTE informado sobre o andamento dos trabalhos;
- Entregar os arquivos e pranchas de cada etapa nos formatos combinados.

## CLÁUSULA 6ª — DAS OBRIGAÇÕES DO CONTRATANTE

- Fornecer as informações, documentos, levantamentos e acessos necessários ao desenvolvimento do projeto;
- Aprovar ou solicitar ajustes em cada etapa no prazo de até 7 (sete) dias após a entrega;
- Efetuar os pagamentos nas datas acordadas;
- Arcar com taxas de órgãos públicos, cópias, plotagens e deslocamentos fora do município, quando houver.

## CLÁUSULA 7ª — DAS ALTERAÇÕES

Alterações solicitadas após a aprovação de uma etapa serão orçadas à parte e formalizadas por aditivo contratual.

## CLÁUSULA 8ª — DOS DIREITOS AUTORAIS

O projeto é obra intelectual protegida pela Lei nº 9.610/1998. O CONTRATANTE fica autorizado a utilizá-lo uma única vez, para a execução da obra no local a que se destina, sendo vedada sua reprodução ou alteração sem autorização da CONTRATADA. A CONTRATADA poderá divulgar imagens do projeto em seu portfólio, preservados os dados pessoais do CONTRATANTE.

## CLÁUSULA 9ª — DA RESCISÃO

O contrato poderá ser rescindido por qualquer das partes, mediante aviso por escrito com 15 (quinze) dias de antecedência. Nesse caso, serão devidos os honorários proporcionais às etapas já executadas ou em andamento.

## CLÁUSULA 10ª — DO FORO

Fica eleito o foro da comarca de {{escritorio.cidade}} para dirimir quaisquer questões oriundas deste contrato.

E, por estarem de acordo, as partes firmam o presente instrumento.

{{escritorio.cidade}}, {{data.extenso}}.
TXT,

        'aditivo' => <<<'TXT'
# TERMO ADITIVO AO CONTRATO DE PRESTAÇÃO DE SERVIÇOS

**CONTRATADA:** {{escritorio.nome}}, CNPJ/CPF {{escritorio.cnpj}}.

**CONTRATANTE:** {{cliente.nome}}, CPF/CNPJ {{cliente.documento}}.

**Projeto:** {{projeto.codigo}} — {{projeto.nome}}.

As partes acima resolvem aditar o contrato de prestação de serviços firmado entre elas, nos termos a seguir.

## CLÁUSULA 1ª — DO OBJETO DO ADITIVO

[Descreva o serviço adicional ou a alteração de escopo solicitada.]

## CLÁUSULA 2ª — DOS HONORÁRIOS ADICIONAIS

Pelos serviços descritos acima, o CONTRATANTE pagará o valor de R$ [valor], em [número] parcela(s), com vencimento em [data].

## CLÁUSULA 3ª — DO PRAZO

O prazo do contrato fica acrescido de [número] dias para a execução dos serviços deste aditivo.

## CLÁUSULA 4ª — DA RATIFICAÇÃO

Permanecem inalteradas e em pleno vigor as demais cláusulas do contrato original que não conflitem com este termo.

{{escritorio.cidade}}, {{data.extenso}}.
TXT,

        'ata' => <<<'TXT'
# ATA DE REUNIÃO

**Projeto:** {{projeto.codigo}} — {{projeto.nome}}
**Cliente:** {{cliente.nome}}
**Data:** {{data.hoje}}
**Local / meio:** [presencial, videochamada…]

## PARTICIPANTES

- {{escritorio.responsavel}} ({{escritorio.nome}})
- {{cliente.nome}}
- [outros participantes]

## ASSUNTOS TRATADOS

- [assunto 1]
- [assunto 2]

## DECISÕES

- [decisão 1]
- [decisão 2]

## PENDÊNCIAS E RESPONSÁVEIS

- [pendência] — responsável: [nome] — prazo: [data]

## PRÓXIMA REUNIÃO

[data e pauta prevista]

As decisões registradas nesta ata passam a orientar o desenvolvimento do projeto.
TXT,

        'termo_aprovacao' => <<<'TXT'
# TERMO DE APROVAÇÃO DE ETAPA

**Projeto:** {{projeto.codigo}} — {{projeto.nome}}
**Contratante:** {{cliente.nome}}, CPF/CNPJ {{cliente.documento}}
**Contratada:** {{escritorio.nome}}

Pelo presente termo, o CONTRATANTE declara que recebeu, analisou e **aprova** a etapa abaixo do projeto:

**Etapa aprovada:** [nome da etapa, ex.: Anteprojeto]
**Material entregue:** [pranchas, imagens, arquivos]

Declara ainda estar ciente de que alterações solicitadas a partir desta aprovação, que modifiquem o que foi aprovado, serão consideradas serviços adicionais, sujeitos a novo orçamento e aditivo contratual.

{{escritorio.cidade}}, {{data.extenso}}.
TXT,

        'termo_encerramento' => <<<'TXT'
# TERMO DE ENCERRAMENTO DE CONTRATO

**Projeto:** {{projeto.codigo}} — {{projeto.nome}}
**Contratante:** {{cliente.nome}}, CPF/CNPJ {{cliente.documento}}
**Contratada:** {{escritorio.nome}}, CNPJ/CPF {{escritorio.cnpj}}

As partes declaram que os serviços objeto do contrato de prestação de serviços de arquitetura referente ao projeto acima foram **integralmente executados e entregues**, compreendendo as etapas:

{{orcamento.escopo}}

Declaram também que os honorários contratados, no valor de {{orcamento.valor}}, foram quitados, nada mais havendo a reclamar uma da outra a qualquer título relativo ao referido contrato, ressalvadas as responsabilidades técnicas previstas em lei.

{{escritorio.cidade}}, {{data.extenso}}.
TXT,
    ];
}

const TIPOS_PERGUNTA = [
    'texto' => 'Resposta curta',
    'texto_longo' => 'Resposta longa',
    'numero' => 'Número',
    'sim_nao' => 'Sim ou não',
    'opcao_unica' => 'Escolha uma opção',
    'multipla' => 'Várias opções',
    'escala' => 'Escala de 1 a 5',
    'data' => 'Data',
];

/** Cada pergunta: [seção, texto, tipo, opções (separadas por |), obrigatória]. */
function modelos_briefing_padrao(): array
{
    $sobreVoces = [
        ['Sobre vocês', 'Quem vai usar o espaço? (nomes, idades, profissões)', 'texto_longo', '', 1],
        ['Sobre vocês', 'Tem animais de estimação? Quais?', 'texto', '', 0],
        ['Sobre vocês', 'Como é a rotina da casa num dia comum?', 'texto_longo', '', 0],
        ['Sobre vocês', 'Recebem visitas com frequência?', 'opcao_unica', 'Raramente|Às vezes|Toda semana|Quase todo dia', 0],
    ];
    $estilo = [
        ['Estilo e referências', 'Quais palavras descrevem o ambiente dos sonhos?', 'multipla', 'Aconchegante|Minimalista|Contemporâneo|Industrial|Clássico|Rústico|Tropical|Escandinavo|Colorido|Neutro', 0],
        ['Estilo e referências', 'Cores que vocês amam', 'texto', '', 0],
        ['Estilo e referências', 'Cores ou materiais que não querem de jeito nenhum', 'texto', '', 0],
        ['Estilo e referências', 'Links de referências (Pinterest, Instagram…)', 'texto_longo', '', 0],
    ];
    $orcamento = [
        ['Prazo e investimento', 'Qual o prazo desejado para a conclusão?', 'data', '', 0],
        ['Prazo e investimento', 'Faixa de investimento prevista para a obra', 'opcao_unica', 'Até R$ 50 mil|R$ 50 a 150 mil|R$ 150 a 300 mil|R$ 300 a 600 mil|Acima de R$ 600 mil|Ainda não sei', 0],
        ['Prazo e investimento', 'O que é mais importante para vocês?', 'opcao_unica', 'Prazo|Custo|Qualidade dos acabamentos|Equilíbrio entre os três', 0],
    ];

    return [
        [
            'nome' => 'Pré-briefing',
            'categoria' => 'Pré-briefing',
            'descricao' => 'Perguntas rápidas para o primeiro contato, antes do orçamento.',
            'perguntas' => [
                ['O projeto', 'Que tipo de projeto você procura?', 'opcao_unica', 'Construção nova|Reforma|Interiores|Comercial|Paisagismo|Outro', 1],
                ['O projeto', 'Onde fica o imóvel? (bairro e cidade)', 'texto', '', 1],
                ['O projeto', 'Área aproximada (m²)', 'numero', '', 0],
                ['O projeto', 'O imóvel já é seu?', 'sim_nao', '', 0],
                ['O projeto', 'Conte em poucas palavras o que você imagina', 'texto_longo', '', 1],
                ['Prazo e investimento', 'Quando gostaria de começar?', 'opcao_unica', 'Imediatamente|Em até 3 meses|Em 3 a 6 meses|Ainda sem data', 0],
                ['Prazo e investimento', 'Faixa de investimento prevista para a obra', 'opcao_unica', 'Até R$ 50 mil|R$ 50 a 150 mil|R$ 150 a 300 mil|Acima de R$ 300 mil|Ainda não sei', 0],
                ['Contato', 'Como prefere ser contatado(a)?', 'opcao_unica', 'WhatsApp|Telefone|E-mail', 0],
            ],
        ],
        [
            'nome' => 'Briefing residencial completo',
            'categoria' => 'Residencial',
            'descricao' => 'Programa de necessidades completo para casas e apartamentos.',
            'perguntas' => array_merge($sobreVoces, [
                ['O imóvel', 'Endereço do imóvel', 'texto', '', 1],
                ['O imóvel', 'Área do terreno ou do imóvel (m²)', 'numero', '', 0],
                ['O imóvel', 'Possui planta, levantamento ou projeto anterior?', 'sim_nao', '', 0],
                ['O imóvel', 'Existe condomínio com regras de obra?', 'sim_nao', '', 0],
                ['Programa de necessidades', 'Quantos quartos? Quantas suítes?', 'texto', '', 1],
                ['Programa de necessidades', 'Ambientes desejados', 'multipla', 'Sala de estar|Sala de jantar|Cozinha aberta|Cozinha fechada|Área gourmet|Home office|Home theater|Lavabo|Lavanderia|Despensa|Closet|Quarto de hóspedes|Piscina|Academia|Jardim|Garagem coberta', 1],
                ['Programa de necessidades', 'Quantas vagas de garagem?', 'numero', '', 0],
                ['Programa de necessidades', 'Alguém trabalha em casa? Precisa de quantas estações?', 'texto', '', 0],
                ['Programa de necessidades', 'Necessidades de acessibilidade', 'texto_longo', '', 0],
                ['Cozinha e convívio', 'Com que frequência cozinham?', 'opcao_unica', 'Quase nunca|Às vezes|Todo dia', 0],
                ['Cozinha e convívio', 'Eletrodomésticos que precisam de espaço (cooktop, forno de embutir, adega…)', 'texto_longo', '', 0],
                ['Conforto', 'Importância de cada item: iluminação natural', 'escala', '', 0],
                ['Conforto', 'Importância de cada item: ventilação natural', 'escala', '', 0],
                ['Conforto', 'Importância de cada item: isolamento acústico', 'escala', '', 0],
                ['Conforto', 'Deseja automação (iluminação, cortinas, som)?', 'sim_nao', '', 0],
                ['Conforto', 'Deseja energia solar ou reaproveitamento de água?', 'sim_nao', '', 0],
            ], $estilo, $orcamento, [
                ['Para terminar', 'Algo mais que devemos saber?', 'texto_longo', '', 0],
            ]),
        ],
        [
            'nome' => 'Briefing de interiores',
            'categoria' => 'Interiores',
            'descricao' => 'Para projetos de interiores e reformas sem mudança estrutural.',
            'perguntas' => array_merge($sobreVoces, [
                ['Ambientes', 'Quais ambientes entram no projeto?', 'multipla', 'Sala|Cozinha|Quarto casal|Quartos filhos|Banheiros|Varanda|Home office|Lavanderia|Hall', 1],
                ['Ambientes', 'Móveis ou objetos que vão permanecer', 'texto_longo', '', 0],
                ['Ambientes', 'Vai trocar pisos e revestimentos?', 'sim_nao', '', 0],
                ['Ambientes', 'Precisa de marcenaria planejada?', 'sim_nao', '', 0],
                ['Ambientes', 'O que mais incomoda hoje no imóvel?', 'texto_longo', '', 0],
                ['Iluminação', 'Como gosta da iluminação à noite?', 'opcao_unica', 'Bem clara|Indireta e aconchegante|Varia por ambiente', 0],
            ], $estilo, $orcamento),
        ],
        [
            'nome' => 'Briefing comercial',
            'categoria' => 'Comercial',
            'descricao' => 'Lojas, escritórios, clínicas, restaurantes e salões.',
            'perguntas' => array_merge([
                ['O negócio', 'Nome e ramo do negócio', 'texto', '', 1],
                ['O negócio', 'Público-alvo', 'texto_longo', '', 1],
                ['O negócio', 'Quantas pessoas trabalham no local?', 'numero', '', 0],
                ['O negócio', 'Quantos clientes atendem ao mesmo tempo, no pico?', 'numero', '', 0],
                ['O negócio', 'Possui identidade visual (logo, cores)? Envie o link', 'texto', '', 0],
                ['O espaço', 'Endereço do imóvel', 'texto', '', 1],
                ['O espaço', 'Área (m²)', 'numero', '', 0],
                ['O espaço', 'Ambientes necessários', 'multipla', 'Recepção|Vitrine|Salão de atendimento|Salas privativas|Copa|Cozinha|Depósito|Banheiro de clientes|Banheiro de funcionários|Vestiário|Escritório', 1],
                ['O espaço', 'Existem exigências da vigilância sanitária, bombeiros ou do shopping?', 'texto_longo', '', 0],
                ['O espaço', 'Equipamentos que precisam de ponto elétrico ou hidráulico especial', 'texto_longo', '', 0],
                ['Experiência', 'Que sensação o cliente deve ter ao entrar?', 'texto_longo', '', 0],
                ['Experiência', 'Concorrentes ou lojas que admira', 'texto_longo', '', 0],
            ], $estilo, $orcamento),
        ],
    ];
}

function seed_documentos(int $eid): void
{
    foreach (modelos_documento_padrao() as $tipo => $texto) {
        if (!val('SELECT id FROM documento_modelo WHERE escritorio_id = ? AND tipo = ?', [$eid, $tipo])) {
            insert('documento_modelo', ['escritorio_id' => $eid, 'tipo' => $tipo, 'conteudo' => $texto, 'atualizado_em' => now()]);
        }
    }
}

function seed_briefings(int $eid): void
{
    if (val('SELECT id FROM briefing_modelo WHERE escritorio_id = ? LIMIT 1', [$eid])) {
        return;
    }
    foreach (modelos_briefing_padrao() as $m) {
        $mid = insert('briefing_modelo', [
            'escritorio_id' => $eid,
            'nome' => $m['nome'],
            'categoria' => $m['categoria'],
            'descricao' => $m['descricao'],
            'ativo' => 1,
        ]);
        foreach ($m['perguntas'] as $i => [$secao, $texto, $tipo, $opcoes, $obrig]) {
            insert('briefing_pergunta', [
                'modelo_id' => $mid,
                'ordem' => $i + 1,
                'secao' => $secao,
                'texto' => $texto,
                'tipo' => $tipo,
                'opcoes' => $opcoes === '' ? null : str_replace('|', "\n", $opcoes),
                'obrigatoria' => $obrig,
            ]);
        }
    }
}
