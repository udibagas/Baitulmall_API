<?php
$dbPath = 'database/database.sqlite';
if (!file_exists($dbPath)) {
    die("Database file not found at $dbPath\n");
}

try {
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $query = "SELECT users.email, users.name, assignments.jabatan 
              FROM users 
              LEFT JOIN people ON users.id = people.user_id 
              LEFT JOIN assignments ON people.id = assignments.person_id";
    
    $stmt = $db->query($query);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Email: {$row['email']} | Nama: {$row['name']} | Jabatan: " . ($row['jabatan'] ?? 'N/A') . "\n";
    }
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
