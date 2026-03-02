<?php
require __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

function get_pdo() {
    $dbUrl = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL') ?? '';
    if ($dbUrl) {
        $parsedUrl = parse_url($dbUrl);
        $dsn = "pgsql:host={$parsedUrl['host']};port=" . ($parsedUrl['port'] ?? '5432') . ";dbname=" . ltrim($parsedUrl['path'], '/');
        return new PDO($dsn, $parsedUrl['user'], $parsedUrl['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    }
    return null;
}

try {
    $pdo = get_pdo();
    
    $counts = [
        'users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
        'people' => $pdo->query("SELECT COUNT(*) FROM people")->fetchColumn(),
        'assignments' => $pdo->query("SELECT COUNT(*) FROM assignments")->fetchColumn(),
        'structures' => $pdo->query("SELECT COUNT(*) FROM organization_structures")->fetchColumn(),
        'signers' => $pdo->query("SELECT COUNT(*) FROM signers")->fetchColumn(),
        'rules' => $pdo->query("SELECT COUNT(*) FROM signature_rules")->fetchColumn(),
        'roles' => $pdo->query("SELECT COUNT(*) FROM roles")->fetchColumn(),
        'asnaf' => $pdo->query("SELECT COUNT(*) FROM asnaf")->fetchColumn(),
    ];
    
    $emails = $pdo->query("SELECT email FROM users ORDER BY email ASC")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "COUNTS:\n" . json_encode($counts, JSON_PRETTY_PRINT) . "\n\n";
    echo "USERS:\n" . implode("\n", $emails);
    
} catch (Exception $e) {
    echo $e->getMessage();
}
