<?php
// Native Vercel bridge - v2.3 (Bypass DATABASE_URL format issues)
require __DIR__ . '/../vendor/autoload.php';

if (!isset($_GET['token']) || $_GET['token'] !== 'BAITULMALL_DEPLOY_2026') {
    header('HTTP/1.1 401 Unauthorized');
    echo 'Unauthorized';
    exit;
}

// Load .env manually
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// Set execution time to 5 minutes
set_time_limit(300);

header('Content-Type: application/json');

function get_pdo() {
    $dbUrl = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL') ?? '';
    
    if ($dbUrl && (str_starts_with($dbUrl, 'postgres://') || str_starts_with($dbUrl, 'postgresql://'))) {
        $parsedUrl = parse_url($dbUrl);
        $dsn = "pgsql:host=" . $parsedUrl['host'] . ";port=" . ($parsedUrl['port'] ?? '5432') . ";dbname=" . ltrim($parsedUrl['path'], '/');
        return new PDO($dsn, $parsedUrl['user'], $parsedUrl['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    }
    return null;
}

$step = $_GET['step'] ?? 'status';

try {
    if ($step === 'status') {
        $pdo = get_pdo();
        if (!$pdo) throw new Exception("Database URL not found");
        
        $counts = [
            'users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
            'asnaf' => $pdo->query("SELECT COUNT(*) FROM asnaf")->fetchColumn(),
            'structures' => $pdo->query("SELECT COUNT(*) FROM organization_structures")->fetchColumn(),
            'rules' => $pdo->query("SELECT COUNT(*) FROM signature_rules")->fetchColumn(),
        ];
        echo json_encode(['status' => 'ok', 'counts' => $counts]);

    } elseif ($step === 'migrate-fresh') {
        $app = require_once __DIR__ . '/../bootstrap/app.php';
        $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
        $kernel->bootstrap();
        
        \Illuminate\Support\Facades\Artisan::call('db:wipe', ['--force' => true]);
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        
        echo json_encode(['status' => 'success', 'output' => \Illuminate\Support\Facades\Artisan::output()]);

    } elseif ($step === 'seed-direct') {
        $app = require_once __DIR__ . '/../bootstrap/app.php';
        $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
        $kernel->bootstrap();

        $results = [];
        
        // Comprehensive list from DatabaseSeeder.php plus new ones
        $seeders = [
            'RTSeeder',
            'AsnafSeeder',
            'SDMSeeder',
            'SignatureSeeder',
            'ZakatFitrahSeeder',
            'SettingSeeder',
            // 'RequestedUsersSeeder', // Disabled to prevent restore of deleted users
            'TransactionalDataSeeder',
            // 'UserAccountSeeder', // Disabled to prevent restore of deleted users
            // 'NewUsersSeeder' // Disabled to prevent restore of deleted users
        ];

        foreach ($seeders as $seeder) {
            try {
                \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => $seeder, '--force' => true]);
                $results[] = [
                    'seeder' => $seeder,
                    'status' => 'OK',
                    'output' => trim(\Illuminate\Support\Facades\Artisan::output())
                ];
            } catch (\Throwable $e) {
                $results[] = [
                    'seeder' => $seeder,
                    'status' => 'ERROR',
                    'message' => $e->getMessage(),
                    'output' => trim(\Illuminate\Support\Facades\Artisan::output())
                ];
            }
        }

        echo json_encode(['status' => 'success', 'results' => $results]);
    }

} catch (\Throwable $e) {
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage(),
        'line'    => $e->getLine()
    ]);
}
