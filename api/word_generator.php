<?php
// api/word_generator.php

function ensureDictionaryHintColumn($pdo) {
    static $checked = false;
    if ($checked) return;
    $checked = true;
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM dictionary LIKE 'hint'")->fetchAll();
        if (empty($cols)) {
            $pdo->exec("ALTER TABLE dictionary ADD COLUMN hint VARCHAR(255) DEFAULT NULL");
        }
    } catch (Exception $e) {}
}

function autoReplenishDictionary($pdo, $lang) {
    ensureDictionaryHintColumn($pdo);

    // Check if we have enough unused words
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM dictionary WHERE language = ? AND used = 0");
    $stmt->execute([$lang]);
    $count = (int)$stmt->fetchColumn();
    
    if ($count >= 5) return; // Fast path: we have enough unused words locally!

    // Recycle already-used words in the local dictionary first (0ms network delay!)
    $stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM dictionary WHERE language = ?");
    $stmtTotal->execute([$lang]);
    $totalInDb = (int)$stmtTotal->fetchColumn();

    if ($totalInDb >= 20) {
        $resetStmt = $pdo->prepare("UPDATE dictionary SET used = 0 WHERE language = ?");
        $resetStmt->execute([$lang]);
        return;
    }

    // Seed local curated dictionary if DB has very few words (still 0ms network delay!)
    $curatedPt = [
        ['CARRO', 'Veículo automotor de quatro rodas para transporte de passageiros.'],
        ['LIVRO', 'Conjunto de folhas impressas e encadernadas para leitura.'],
        ['PRAIA', 'Faixa de areia à beira do mar ou de um rio.'],
        ['NUVEM', 'Massa visível de gotículas de água suspensa na atmosfera.'],
        ['CHUVA', 'Precipitação de água em forma de gotas que caem das nuvens.'],
        ['TIGRE', 'Grande felino asiático de pelagem alaranjada com listras pretas.'],
        ['AGUIA', 'Ave de rapina diurna conhecida pela visão aguçada e voo alto.'],
        ['CAVALO', 'Mamífero quadrúpede domesticado usado para montaria e tração.'],
        ['JANELA', 'Abertura na parede de um edifício para entrada de luz e ar.'],
        ['CHAVE', 'Utensílio de metal usado para abrir ou fechar fechaduras.'],
        ['VIOLAO', 'Instrumento musical de cordas dedilhadas com caixa de ressonância.'],
        ['ESCOLA', 'Instituição destinada ao ensino e aprendizagem de alunos.'],
        ['JARDIM', 'Terreno onde se cultivam flores, plantas ornamentais e árvores.'],
        ['PONTE', 'Construção que liga dois pontos separados por um rio ou vale.'],
        ['RELOGIO', 'Instrumento utilizado para medir e indicar as horas do dia.'],
        ['ESPELHO', 'Superfície polida que reflete a luz e forma imagens.'],
        ['CASTELO', 'Edificação fortificada medieval, residência de reis ou nobres.'],
        ['FOGUETE', 'Veículo espacial impulsionado por motores de reação.'],
        ['BUSSOLA', 'Instrumento de navegação com agulha magnetizada que aponta o norte.'],
        ['VULCAO', 'Estrutura geológica por onde magma e gases são expelidos.'],
        ['PIRATA', 'Navegador que cruzava os mares para saquear embarcações.'],
        ['TESOURO', 'Conjunto de riquezas, moedas de ouro ou joias guardadas.'],
        ['OCEANO', 'Grande extensão de água salgada que cobre a maior parte da Terra.'],
        ['FLORESTA', 'Grande área coberta por alta densidade de árvores e vida silvestre.'],
        ['GUITARRA', 'Instrumento musical de cordas, muito usado no rock e blues.'],
        ['DIAMANTE', 'Pedra preciosa cristalina composta por carbono puro de alta dureza.'],
        ['LANTERNA', 'Aparelho portátil que emite foco de luz para iluminação.'],
        ['MOCHILA', 'Bolsa carregada nas costas por meio de duas alças.'],
        ['TECLADO', 'Conjunto de teclas usado para digitar ou tocar música.'],
        ['TROVAO', 'Estrondo sonoro produzido pela expansão do ar após um raio.']
    ];

    if ($lang === 'pt') {
        $ins = $pdo->prepare("INSERT IGNORE INTO dictionary (word, language, used, hint) VALUES (?, 'pt', 0, ?)");
        foreach ($curatedPt as $item) {
            $ins->execute([$item[0], $item[1]]);
        }
    }
}

function fetchQuickHint($lang, $title) {
    $hint = null;
    if ($lang === 'pt') {
        $dicUrl = "https://api.dicionario-aberto.net/word/" . urlencode(mb_strtolower($title, 'UTF-8'));
        $chDic = curl_init();
        curl_setopt($chDic, CURLOPT_URL, $dicUrl);
        curl_setopt($chDic, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($chDic, CURLOPT_CONNECTTIMEOUT_MS, 700);
        curl_setopt($chDic, CURLOPT_TIMEOUT_MS, 1000);
        $dicRes = curl_exec($chDic);
        curl_close($chDic);
        
        if ($dicRes) {
            $dicData = json_decode($dicRes, true);
            if (!empty($dicData) && isset($dicData[0]['xml'])) {
                if (preg_match('/<def>(.*?)<\/def>/s', $dicData[0]['xml'], $matches)) {
                    $def = strip_tags($matches[1]);
                    $def = str_replace('_', '', $def);
                    $lines = explode("\n", trim($def));
                    $hint = trim($lines[0]);
                }
            }
        }
    } else if ($lang === 'en' || $lang === 'es') {
        $dicUrl = "https://api.dictionaryapi.dev/api/v2/entries/{$lang}/" . urlencode(mb_strtolower($title, 'UTF-8'));
        $chDic = curl_init();
        curl_setopt($chDic, CURLOPT_URL, $dicUrl);
        curl_setopt($chDic, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($chDic, CURLOPT_CONNECTTIMEOUT_MS, 700);
        curl_setopt($chDic, CURLOPT_TIMEOUT_MS, 1000);
        $dicRes = curl_exec($chDic);
        curl_close($chDic);
        
        if ($dicRes) {
            $dicData = json_decode($dicRes, true);
            if (isset($dicData[0]['meanings'][0]['definitions'][0]['definition'])) {
                $hint = $dicData[0]['meanings'][0]['definitions'][0]['definition'];
            }
        }
    }
    return $hint;
}

function generateDailyWords($pdo, $lang, $count = 7) {
    ensureDictionaryHintColumn($pdo);

    // 1. Replenish / recycle dictionary locally (instantaneous!)
    autoReplenishDictionary($pdo, $lang);
    
    $words = [];
    
    // Prefer words that already have a cached hint first for 0ms latency
    $stmt = $pdo->prepare("SELECT id, word, hint FROM dictionary WHERE language = ? AND used = 0 ORDER BY (hint IS NOT NULL AND hint != '') DESC, RAND() LIMIT " . (int)$count);
    $stmt->execute([$lang]);
    $results = $stmt->fetchAll();
    
    $idsToMarkUsed = [];
    
    foreach ($results as $row) {
        $title = $row['word'];
        
        // Fix CP850/CP437 double encoding (e.g., ON├çA instead of ONÇA)
        if (strpos($title, '├') !== false || strpos($title, 'Ã') !== false) {
            $fixed = mb_convert_encoding($title, 'CP850', 'UTF-8');
            if ($fixed && mb_check_encoding($fixed, 'UTF-8')) {
                $title = $fixed;
            }
        }
        
        $idsToMarkUsed[] = $row['id'];
        $hint = !empty($row['hint']) ? $row['hint'] : null;
        
        // Only query external API with a strict 1.0s max budget if hint is not cached in DB
        if (!$hint) {
            $hint = fetchQuickHint($lang, $title);
        }
        
        if (!$hint) {
            $len = mb_strlen($title, 'UTF-8');
            $firstChar = mb_substr(mb_strtoupper($title, 'UTF-8'), 0, 1, 'UTF-8');
            $hint = ($lang === 'en')
                ? "Word with {$len} letters starting with '{$firstChar}'."
                : (($lang === 'es')
                    ? "Palabra de {$len} letras que empieza con '{$firstChar}'."
                    : "Palavra de {$len} letras que começa com '{$firstChar}'.");
        }
        
        // Hide the secret word
        $hint = preg_replace('/' . preg_quote($title, '/') . '/i', '_____', $hint);
        
        // Truncate to a reasonable length
        if (mb_strlen($hint, 'UTF-8') > 80) {
            $hint = mb_substr($hint, 0, 80, 'UTF-8') . '...';
        }
        
        $finalHint = mb_substr($hint, 0, 255, 'UTF-8');

        // Cache the resolved hint back to the dictionary table so future matches are 0ms!
        if (empty($row['hint'])) {
            try {
                $cacheStmt = $pdo->prepare("UPDATE dictionary SET hint = ? WHERE id = ?");
                $cacheStmt->execute([$finalHint, $row['id']]);
            } catch (Exception $e) {}
        }
        
        $words[] = [
            'word' => mb_strtoupper($title, 'UTF-8'),
            'hint' => $finalHint
        ];
    }
    
    // Mark as used
    if (!empty($idsToMarkUsed)) {
        $placeholders = implode(',', array_fill(0, count($idsToMarkUsed), '?'));
        $updateStmt = $pdo->prepare("UPDATE dictionary SET used = 1 WHERE id IN ($placeholders)");
        $updateStmt->execute($idsToMarkUsed);
    }
    
    // Fallback localized
    $fallbacks = [
        'pt' => ['PLANETA', 'COMETA', 'GALAXIA', 'ESTRELA', 'SISTEMA', 'QUASAR', 'BURACO'],
        'en' => ['PLANET', 'COMET', 'GALAXY', 'STAR', 'SYSTEM', 'QUASAR', 'HOLE'],
        'es' => ['PLANETA', 'COMETA', 'GALAXIA', 'ESTRELLA', 'SISTEMA', 'CUASAR', 'AGUJERO']
    ];
    
    $fallbackArr = isset($fallbacks[$lang]) ? $fallbacks[$lang] : $fallbacks['en'];
    $fallbackHint = ($lang === 'en') ? 'Extra word' : (($lang === 'es') ? 'Palabra extra' : 'Palavra extra');
    
    while(count($words) < $count) {
        $words[] = ['word' => $fallbackArr[array_rand($fallbackArr)], 'hint' => $fallbackHint];
    }
    
    return $words;
}
?>
