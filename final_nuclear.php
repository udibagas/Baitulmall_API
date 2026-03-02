<?php
require __DIR__.'/vendor/autoload.php';

function check_db($name, $connection_config) {
    echo "--- Checking $name ---\n";
    try {
        if ($name === 'sqlite') {
            $pdo = new PDO("sqlite:".__DIR__."/database/database.sqlite");
        } else {
            $dsn = "pgsql:host={$connection_config['host']};port={$connection_config['port']};dbname={$connection_config['dbname']}";
            $pdo = new PDO($dsn, $connection_config['user'], $connection_config['pass']);
        }
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='roles'");
        if ($name !== 'sqlite') {
            $stmt = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_name='roles'");
        }
        
        if ($stmt->fetch()) {
            $res = $pdo->query("SELECT id, name FROM roles")->fetchAll(PDO::FETCH_ASSOC);
            echo "Roles found (" . count($res) . "): " . implode(', ', array_column($res, 'name')) . "\n";
            if (count($res) > 0) {
                echo "Deleting all roles from $name...\n";
                $pdo->exec("DELETE FROM roles");
                echo "Success.\n";
            }
        } else {
            echo "Table 'roles' NOT FOUND in $name.\n";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

$supabase = [
    'host' => 'aws-1-ap-southeast-2.pooler.supabase.com',
    'port' => '6543',
    'dbname' => 'postgres',
    'user' => 'postgres.mjngadqjdqbgzzsympfr',
    'pass' => 'I4tEJNnSAuwjYkSr'
];

check_db('sqlite', []);
check_db('supabase', $supabase);
