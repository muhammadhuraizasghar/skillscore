<?php
require_once __DIR__ . '/ai.php';
class Database {
    private static ?\mysqli $conn = null;
    public static function conn(): \mysqli {
        if (self::$conn instanceof \mysqli) {
            return self::$conn;
        }
        $cfg = __DIR__ . '/config.local.php';
        if (file_exists($cfg)) { require_once $cfg; }
        $host = getenv('DB_HOST') ?: (defined('DB_HOST') ? DB_HOST : 'sdb-89.hosting.stackcp.net');
        $user = getenv('DB_USER') ?: (defined('DB_USER') ? DB_USER : 'board_skills_html-35313137218a');
        $pass = getenv('DB_PASS') ?: (defined('DB_PASS') ? DB_PASS : 'huraizart123@');
        $name = getenv('DB_NAME') ?: (defined('DB_NAME') ? DB_NAME : 'board_skills_html-35313137218a');
        $conn = new \mysqli($host, $user, $pass, $name);
        if ($conn->connect_error) {
            http_response_code(500);
            die('Database connection failed');
        }
        $conn->set_charset('utf8mb4');
        self::$conn = $conn;
        return self::$conn;
    }
}
function ensure_dirs(): void {
    $dirs = ['uploads', 'certificates'];
    foreach ($dirs as $d) {
        if (!is_dir($d)) {
            mkdir($d, 0775, true);
        }
    }
}
function sanitize_filename(string $name): string {
    $name = preg_replace('/[^A-Za-z0-9_\.-]/', '_', $name);
    return $name ?: uniqid('file_', true);
}
function store_upload(array $file, string $subdir = 'uploads'): ?string {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return null;
    }
    ensure_dirs();
    $base = basename($file['name'] ?? 'uploaded');
    $safe = sanitize_filename($base);
    $ext = pathinfo($safe, PATHINFO_EXTENSION);
    $name = pathinfo($safe, PATHINFO_FILENAME);
    $targetName = $name . '_' . time() . ($ext ? '.' . $ext : '');
    $targetPath = ($subdir === 'uploads' ? 'uploads' : $subdir) . DIRECTORY_SEPARATOR . $targetName;
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return null;
    }
    return $targetPath;
}
function json_response($data, int $code = 200): void {
    header('Content-Type: application/json');
    http_response_code($code);
    echo json_encode($data);
    exit;
}
?>
