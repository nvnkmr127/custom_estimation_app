<?php
$dbPath = __DIR__ . '/../database/database.sqlite';
if (!file_exists($dbPath)) {
    die("Database file not found at $dbPath\n");
}

try {
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== COLUMNS IN estimate_items ===\n";
    $columns = $db->query("PRAGMA table_info(estimate_items)")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "{$col['name']} - {$col['type']}\n";
    }

    echo "\n=== COLUMNS IN clients ===\n";
    $columns = $db->query("PRAGMA table_info(clients)")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "{$col['name']} - {$col['type']}\n";
    }

    echo "\n=== RECENT ESTIMATE ITEMS (FIRST 2) ===\n";
    $items = $db->query("SELECT * FROM estimate_items LIMIT 2")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($items as $item) {
        print_r($item);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
