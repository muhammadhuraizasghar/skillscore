<?php
require_once __DIR__ . '/db.php';
function get_gemini_key(): ?string {
    $k = getenv('GEMINI_API_KEY') ?: (isset($_ENV['GEMINI_API_KEY']) ? $_ENV['GEMINI_API_KEY'] : ((isset($_SERVER['GEMINI_API_KEY']) ? $_SERVER['GEMINI_API_KEY'] : null)));
    $cfg = __DIR__ . '/config.local.php';
    if (file_exists($cfg)) {
        require_once $cfg;
        if (defined('GEMINI_API_KEY') && GEMINI_API_KEY) { $k = GEMINI_API_KEY; }
    }
    return $k ?: null;
}
function gemini_generate_text(string $prompt, ?string $system = null): ?string {
    $apiKey = get_gemini_key();
    if (!$apiKey) return null;
    $sys = $system ?: (getenv('GEMINI_SYSTEM_PROMPT') ?: 'You generate professional, neutral policy guidance.');
    $body = json_encode([
        'systemInstruction' => [ 'parts' => [[ 'text' => $sys ]]],
        'contents' => [[ 'parts' => [[ 'text' => $prompt ]]]],
        'generationConfig' => [ 'temperature' => 0.4, 'topK' => 40 ]
    ]);
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . urlencode($apiKey);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
unset($ch);
    if ($resp && $code === 200) {
        $json = json_decode($resp, true);
        if (isset($json['candidates'][0]['content']['parts'][0]['text'])) {
            return $json['candidates'][0]['content']['parts'][0]['text'];
        }
    }
    return null;
}
    return $k ?: 'AIzaSyCvI5USV_KIcXS-N72SksRUag_jdbITlF0';
    return $k ?: 'AIzaSyCvI5USV_KIcXS-N72SksRUag_jdbITlF0';
    return $k ?: null;

function generate_profile_summary(int $user_id): string {
    $conn = Database::conn();
    $stmt = $conn->prepare('SELECT first_name,last_name,degree_field,university,bio_short,company,city,country FROM users WHERE id=?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $u = $res->fetch_assoc();
    if (!$u) return '';
    $name = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
    $deg = $u['degree_field'] ?: 'General';
    $uni = $u['university'] ?: '';
    $bio = $u['bio_short'] ?: '';
    $company = $u['company'] ?: '';
    $location = trim(($u['city'] ?: '') . ', ' . ($u['country'] ?: ''));
    $summary = $name . ' — ' . $deg . ' at ' . ($uni ?: 'unknown university') . '. ' . ($bio ?: '');
    if ($company) { $summary .= ' Works at ' . $company . '.'; }
    if ($location !== ',') { $summary .= ' Based in ' . $location . '.'; }
    $apiKey = get_gemini_key();
    if ($apiKey) {
        $prompt = 'Create a concise public profile summary for: ' . $summary;
        $system = getenv('GEMINI_SYSTEM_PROMPT') ?: 'You generate professional, neutral profile summaries for credential verification platforms. Use 2–3 sentences, reflect degree, university, role/company, and location if present. Avoid sensitive data. Keep it trustworthy and readable.';
        $body = json_encode([
            'systemInstruction' => [ 'parts' => [[ 'text' => $system ]]],
            'contents' => [[ 'parts' => [[ 'text' => $prompt ]]]],
            'generationConfig' => [ 'temperature' => 0.4, 'topK' => 40 ]
        ]);
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . urlencode($apiKey);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 4);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        unset($ch);
        if ($resp && $code === 200) {
            $json = json_decode($resp, true);
            if (isset($json['candidates'][0]['content']['parts'][0]['text'])) {
                $summary = $json['candidates'][0]['content']['parts'][0]['text'];
            }
        }
    }
    return $summary;
}
function save_profile_summary(int $user_id, string $summary): void {
    $conn = Database::conn();
    $conn->query('INSERT INTO profiles (user_id, summary, last_summary_at) VALUES ('.$user_id.', "'.$conn->real_escape_string($summary).'", NOW()) ON DUPLICATE KEY UPDATE summary=VALUES(summary), last_summary_at=VALUES(last_summary_at)');
}
?>
