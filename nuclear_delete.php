<?php
// Bare PDO check
$host = 'aws-1-ap-southeast-2.pooler.supabase.com';
$port = '6543';
$dbname = 'postgres';
$user = 'postgres.mjngadqjdqbgzzsympfr';
$pass = 'I4tEJNnSAuwjYkSr';

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    echo "Connected to Supabase.\n";
    
    $stmt = $pdo->query("SELECT id, name FROM roles");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Current Roles in DB (" . count($roles) . "):\n";
    foreach($roles as $r) {
        echo "- ID: {$r['id']}, Name: {$r['name']}\n";
    }
    
    if (count($roles) > 0) {
        echo "Deleting roles...\n";
        $pdo->exec("DELETE FROM roles");
        echo "All roles deleted.\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
