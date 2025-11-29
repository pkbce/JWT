<?php
$dbPath = __DIR__ . '/database/database.sqlite';
try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Checking reset_logs table...\n";
    $stmt = $pdo->query("SELECT * FROM reset_logs");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($logs)) {
        echo "Table is empty or does not exist.\n";
    } else {
        foreach ($logs as $log) {
            echo "Type: {$log['reset_type']}, Last Reset: {$log['last_reset_at']}\n";
        }
    }
    
    echo "\nCurrent Time: " . date('Y-m-d H:i:s') . "\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
