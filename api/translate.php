<?php
require __DIR__ . '/_bootstrap.php';
if (!in_array(user()['role'], ['admin','manager'], true)) json_response(['error' => 'forbidden'], 403);

$in = input();
$name = trim((string)($in['name'] ?? ''));
$description = trim((string)($in['description'] ?? ''));
$allergens = trim((string)($in['allergens'] ?? ''));
if (!$name) json_response(['error' => 'nome richiesto']);

// Cerca API key OpenAI (env o config.local)
$apiKey = getenv('OPENAI_API_KEY') ?: '';
if (!$apiKey) {
    $cfg = file_exists(__DIR__ . '/../includes/config.local.php') ? require __DIR__ . '/../includes/config.local.php' : [];
    $apiKey = $cfg['openai_key'] ?? '';
}
if (!$apiKey) json_response(['error' => 'OpenAI API key non configurata su questo server (aggiungi openai_key in config.local.php)']);

$prompt = "Traduci il seguente nome di piatto/bevanda italiano in inglese, arabo, spagnolo, francese e tedesco. Restituisci SOLO un JSON valido con questa struttura esatta (senza markdown, senza commenti):\n"
    . '{"en":{"name":"...","description":"...","allergens":"..."},"ar":{"name":"...","description":"...","allergens":"..."},"es":{"name":"...","description":"...","allergens":"..."},"fr":{"name":"...","description":"...","allergens":"..."},"de":{"name":"...","description":"...","allergens":"..."}}'
    . "\n\nMantieni lo stile breve da menu ristorante. L'arabo deve essere in arabo moderno standard (MSA), adatto a un ristorante a Sharm El Sheikh in Egitto. Se la descrizione o gli allergeni sono vuoti, ritornali vuoti.\n\n"
    . "Nome: $name\nDescrizione: $description\nAllergeni: $allergens";

$payload = json_encode([
    'model' => 'gpt-4o-mini',
    'messages' => [
        ['role' => 'system', 'content' => 'Sei un traduttore esperto di menu ristoranti. Rispondi solo con JSON valido.'],
        ['role' => 'user', 'content' => $prompt],
    ],
    'response_format' => ['type' => 'json_object'],
    'temperature' => 0.3,
]);
$ch = curl_init('https://api.openai.com/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $apiKey],
    CURLOPT_TIMEOUT => 30,
]);
$resp = curl_exec($ch);
if (curl_errno($ch)) json_response(['error' => curl_error($ch)]);
$j = json_decode($resp, true);
if (!isset($j['choices'][0]['message']['content'])) json_response(['error' => 'risposta API non valida', 'detail' => $j['error']['message'] ?? '']);
$content = json_decode($j['choices'][0]['message']['content'], true);
if (!is_array($content)) json_response(['error' => 'JSON traduzione invalido']);
json_response(['ok' => true, 'translations' => $content]);
