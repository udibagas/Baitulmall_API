<?php
$db = new PDO('sqlite:d:/Baitulmall/Baitulmall_API/database/diag.sqlite');

echo "--- ROLES ---\n";
$stmt = $db->query('SELECT id, name, permissions FROM roles');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$row['id']}, Name: '{$row['name']}', Permissions: {$row['permissions']}\n";
}

echo "\n--- ASSIGNMENTS and ROLES ---\n";
$stmt = $db->query('
    SELECT a.jabatan, a.person_id, u.email, u.name as user_name, r.id as role_id, r.permissions
    FROM assignments a
    JOIN persons p ON a.person_id = p.id
    JOIN users u ON p.user_id = u.id
    LEFT JOIN roles r ON a.jabatan = r.name
    WHERE a.status IN ("Aktif", "aktif")
');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "User: {$row['user_name']} ({$row['email']}), Jabatan: '{$row['jabatan']}', Role ID: " . ($row['role_id'] ?? 'NULL') . "\n";
    if ($row['role_id']) {
        echo "  -> Permissions: {$row['permissions']}\n";
    }
}
unlink('d:/Baitulmall/Baitulmall_API/database/diag.sqlite');
