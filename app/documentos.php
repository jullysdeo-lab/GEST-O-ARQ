<?php
declare(strict_types=1);

const DADOS_ESCRITORIO = [
    'esc_razao' => 'Nome ou razão social',
    'esc_cnpj' => 'CNPJ ou CPF',
    'esc_endereco' => 'Endereço completo',
    'esc_cidade' => 'Cidade / UF',
    'esc_responsavel' => 'Responsável técnico',
    'esc_cau' => 'Registro no CAU',
    'esc_email' => 'E-mail',
    'esc_telefone' => 'Telefone / WhatsApp',
];

const MESES_EXTENSO = ['', 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];

function conf_texto(string $chave, string $padrao = ''): string
{
    $v = val('SELECT valor FROM config WHERE escritorio_id = ? AND chave = ?', [eid(), $chave]);
    return $v === null ? $padrao : (string) $v;
}

function set_conf_texto(string $chave, string $valor): void
{
    $valor = mb_substr($valor, 0, 255);
    $id = val('SELECT id FROM config WHERE escritorio_id = ? AND chave = ?', [eid(), $chave]);
    if ($id) {
        update('config', ['valor' => $valor], 'id = :id', ['id' => $id]);
    } else {
        insert('config', ['escritorio_id' => eid(), 'chave' => $chave, 'valor' => $valor]);
    }
}

function novo_token(): string
{
    return bin2hex(random_bytes(24));
}

function data_extenso(?string $data = null): string
{
    $t = $data ? strtotime($data) : time();
    return sprintf('%d de %s de %d', (int) date('j', $t), MESES_EXTENSO[(int) date('n', $t)], (int) date('Y', $t));
}

/** Número inteiro por extenso, em português (até bilhões). */
function numero_extenso(int $n): string
{
    if ($n === 0) {
        return 'zero';
    }
    $unidades = ['', 'um', 'dois', 'três', 'quatro', 'cinco', 'seis', 'sete', 'oito', 'nove', 'dez', 'onze', 'doze', 'treze', 'quatorze', 'quinze', 'dezesseis', 'dezessete', 'dezoito', 'dezenove'];
    $dezenas = ['', '', 'vinte', 'trinta', 'quarenta', 'cinquenta', 'sessenta', 'setenta', 'oitenta', 'noventa'];
    $centenas = ['', 'cento', 'duzentos', 'trezentos', 'quatrocentos', 'quinhentos', 'seiscentos', 'setecentos', 'oitocentos', 'novecentos'];

    $ate999 = function (int $x) use ($unidades, $dezenas, $centenas): string {
        if ($x === 100) {
            return 'cem';
        }
        $partes = [];
        if ($x >= 100) {
            $partes[] = $centenas[intdiv($x, 100)];
            $x %= 100;
        }
        if ($x >= 20) {
            $partes[] = $dezenas[intdiv($x, 10)];
            $x %= 10;
        }
        if ($x > 0) {
            $partes[] = $unidades[$x];
        }
        return implode(' e ', $partes);
    };

    $nomes = [['bilhão', 'bilhões'], ['milhão', 'milhões'], ['mil', 'mil'], ['', '']];
    $divisores = [1_000_000_000, 1_000_000, 1_000, 1];
    $pedacos = []; // [texto, valor do grupo]
    $resto = $n;
    foreach ($divisores as $i => $d) {
        $q = intdiv($resto, $d);
        $resto %= $d;
        if ($q === 0) {
            continue;
        }
        $txt = ($d === 1_000 && $q === 1) ? 'mil' : trim($ate999($q) . ' ' . $nomes[$i][$q === 1 ? 0 : 1]);
        $pedacos[] = [$txt, $q];
    }
    // O último grupo entra com "e" quando é menor que 100 ou centena redonda:
    // "mil e quinhentos", "dois mil e vinte", mas "mil duzentos e trinta".
    $out = $pedacos[0][0];
    $total = count($pedacos);
    for ($k = 1; $k < $total; $k++) {
        [$txt, $q] = $pedacos[$k];
        $usaE = $k === $total - 1 && ($q < 100 || $q % 100 === 0);
        $out .= ($usaE ? ' e ' : ' ') . $txt;
    }
    return $out;
}

function valor_extenso(float $valor): string
{
    $centavos = (int) round($valor * 100);
    $reais = intdiv($centavos, 100);
    $cent = $centavos % 100;
    $partes = [];
    if ($reais > 0) {
        $txt = numero_extenso($reais);
        // "um milhão de reais", "dois mil reais"
        $de = preg_match('/(milhão|milhões|bilhão|bilhões)$/u', $txt) ? ' de' : '';
        $partes[] = $txt . $de . ($reais === 1 ? ' real' : ' reais');
    }
    if ($cent > 0) {
        $partes[] = numero_extenso($cent) . ($cent === 1 ? ' centavo' : ' centavos');
    }
    return $partes ? implode(' e ', $partes) : 'zero real';
}

/** Monta os valores dos campos {{...}} para um documento. */
function contexto_documento(int $clienteId, ?int $projetoId, ?int $orcamentoId): array
{
    $c = one('SELECT * FROM cliente WHERE id = ? AND escritorio_id = ?', [$clienteId, eid()]) ?? [];
    $p = $projetoId ? (one('SELECT * FROM projeto WHERE id = ? AND escritorio_id = ?', [$projetoId, eid()]) ?? []) : [];
    if (!$orcamentoId && !empty($p['orcamento_id'])) {
        $orcamentoId = (int) $p['orcamento_id'];
    }
    $o = $orcamentoId ? (one('SELECT * FROM orcamento WHERE id = ? AND escritorio_id = ?', [$orcamentoId, eid()]) ?? []) : [];

    $escopo = '';
    $prazo = 0;
    if ($o) {
        $etapas = all(
            'SELECT etapa_ordem, etapa_nome, MAX(duracao_dias) AS dias FROM orcamento_item
             WHERE orcamento_id = ? GROUP BY etapa_ordem, etapa_nome ORDER BY etapa_ordem',
            [$o['id']]
        );
        foreach ($etapas as $et) {
            $tarefas = all('SELECT tarefa_nome FROM orcamento_item WHERE orcamento_id = ? AND etapa_ordem = ? ORDER BY id', [$o['id'], $et['etapa_ordem']]);
            $escopo .= "- **{$et['etapa_ordem']}. {$et['etapa_nome']}**: " . implode('; ', array_column($tarefas, 'tarefa_nome')) . ".\n";
            $prazo += (int) $et['dias'];
        }
    }

    $nomeEsc = conf_texto('esc_razao') ?: (string) val('SELECT nome FROM escritorio WHERE id = ?', [eid()]);
    $faltando = '[preencher]';
    $valor = (float) ($o['valor_total'] ?? 0);
    $parcelas = max(1, (int) ($o['parcelas'] ?? 1));

    return [
        'escritorio.nome' => $nomeEsc,
        'escritorio.cnpj' => conf_texto('esc_cnpj') ?: $faltando,
        'escritorio.endereco' => conf_texto('esc_endereco') ?: $faltando,
        'escritorio.cidade' => conf_texto('esc_cidade') ?: $faltando,
        'escritorio.responsavel' => conf_texto('esc_responsavel') ?: (current_user()['nome'] ?? $faltando),
        'escritorio.cau' => conf_texto('esc_cau') ?: $faltando,
        'escritorio.email' => conf_texto('esc_email') ?: $faltando,
        'escritorio.telefone' => conf_texto('esc_telefone') ?: $faltando,
        'cliente.nome' => $c['nome'] ?? $faltando,
        'cliente.documento' => ($c['documento'] ?? '') ?: $faltando,
        'cliente.endereco' => ($c['endereco'] ?? '') ?: $faltando,
        'cliente.email' => ($c['email'] ?? '') ?: $faltando,
        'cliente.telefone' => ($c['telefone'] ?? '') ?: $faltando,
        'projeto.codigo' => $p['codigo'] ?? '—',
        'projeto.nome' => $p['nome'] ?? ($o['titulo'] ?? $faltando),
        'projeto.inicio' => !empty($p['inicio']) ? brdate($p['inicio']) : $faltando,
        'projeto.entrega' => !empty($p['entrega']) ? brdate($p['entrega']) : $faltando,
        'orcamento.numero' => $o['numero'] ?? $faltando,
        'orcamento.valor' => $o ? money($valor) : 'R$ ' . $faltando,
        'orcamento.valor_extenso' => $o ? valor_extenso($valor) : $faltando,
        'orcamento.parcelas' => (string) $parcelas,
        'orcamento.valor_parcela' => $o ? money($valor / $parcelas) : 'R$ ' . $faltando,
        'orcamento.area' => !empty($o['area_m2']) ? num($o['area_m2'], 2) : $faltando,
        'orcamento.escopo' => $escopo !== '' ? rtrim($escopo) : '- ' . $faltando,
        'orcamento.prazo_dias' => $prazo > 0 ? (string) $prazo : $faltando,
        'data.hoje' => date('d/m/Y'),
        'data.extenso' => data_extenso(),
    ];
}

const CAMPOS_DOCUMENTO = [
    'Escritório' => ['escritorio.nome', 'escritorio.cnpj', 'escritorio.endereco', 'escritorio.cidade', 'escritorio.responsavel', 'escritorio.cau', 'escritorio.email', 'escritorio.telefone'],
    'Cliente' => ['cliente.nome', 'cliente.documento', 'cliente.endereco', 'cliente.email', 'cliente.telefone'],
    'Projeto' => ['projeto.codigo', 'projeto.nome', 'projeto.inicio', 'projeto.entrega'],
    'Orçamento' => ['orcamento.numero', 'orcamento.valor', 'orcamento.valor_extenso', 'orcamento.parcelas', 'orcamento.valor_parcela', 'orcamento.area', 'orcamento.escopo', 'orcamento.prazo_dias'],
    'Datas' => ['data.hoje', 'data.extenso'],
];

function preencher_campos(string $texto, array $ctx): string
{
    return preg_replace_callback('/\{\{\s*([a-z_.]+)\s*\}\}/', fn($m) => $ctx[$m[1]] ?? $m[0], $texto);
}

/**
 * Converte o texto do documento em HTML seguro.
 * "# " título, "## " seção, "- " lista, **negrito**, linha em branco separa parágrafos.
 */
function doc_html(string $texto): string
{
    $inline = function (string $s): string {
        $s = e($s);
        $s = preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $s);
        // Destaca o que ainda precisa ser preenchido.
        return preg_replace('/\[([^\]\n]{1,80})\]/u', '<mark>[$1]</mark>', $s);
    };
    $html = '';
    $lista = false;
    $paragrafo = [];
    $fechaParagrafo = function () use (&$paragrafo, &$html, $inline) {
        if ($paragrafo) {
            $html .= '<p>' . implode('<br>', array_map($inline, $paragrafo)) . "</p>\n";
            $paragrafo = [];
        }
    };
    foreach (preg_split('/\R/', $texto) as $linha) {
        $l = rtrim($linha);
        if (str_starts_with($l, '- ')) {
            $fechaParagrafo();
            if (!$lista) {
                $html .= "<ul>\n";
                $lista = true;
            }
            $html .= '<li>' . $inline(substr($l, 2)) . "</li>\n";
            continue;
        }
        if ($lista) {
            $html .= "</ul>\n";
            $lista = false;
        }
        if ($l === '') {
            $fechaParagrafo();
        } elseif (str_starts_with($l, '## ')) {
            $fechaParagrafo();
            $html .= '<h3>' . $inline(substr($l, 3)) . "</h3>\n";
        } elseif (str_starts_with($l, '# ')) {
            $fechaParagrafo();
            $html .= '<h2>' . $inline(substr($l, 2)) . "</h2>\n";
        } else {
            $paragrafo[] = $l;
        }
    }
    $fechaParagrafo();
    if ($lista) {
        $html .= "</ul>\n";
    }
    return $html;
}

/** Trechos entre colchetes que ainda precisam ser preenchidos, ex.: [preencher], [valor]. */
function pendencias_documento(string $texto): array
{
    preg_match_all('/\[[^\]\n]{1,80}\]/u', $texto, $m);
    return array_values(array_unique($m[0]));
}

function cliente_ip(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
}
