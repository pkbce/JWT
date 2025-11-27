<?php
try {
    $pdo = new PDO('sqlite:database/database.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $tables = ['light_loads', 'medium_loads', 'heavy_loads', 'universal_loads'];

    // Columns to add if they don't exist
    $columns = [
        'h4' => 'INT DEFAULT 0',
        'h8' => 'INT DEFAULT 0',
        'h12' => 'INT DEFAULT 0',
        'h16' => 'INT DEFAULT 0',
        'h20' => 'INT DEFAULT 0',
        'h24' => 'INT DEFAULT 0',
        'mon' => 'INT DEFAULT 0',
        'tue' => 'INT DEFAULT 0',
        'wed' => 'INT DEFAULT 0',
        'thu' => 'INT DEFAULT 0',
        'fri' => 'INT DEFAULT 0',
        'sat' => 'INT DEFAULT 0',
        'sun' => 'INT DEFAULT 0',
        'week1' => 'INT DEFAULT 0',
        'week2' => 'INT DEFAULT 0',
        'week3' => 'INT DEFAULT 0',
        'week4' => 'INT DEFAULT 0',
        'jan' => 'INT DEFAULT 0',
        'feb' => 'INT DEFAULT 0',
        'mar' => 'INT DEFAULT 0',
        'apr' => 'INT DEFAULT 0',
        'may' => 'INT DEFAULT 0',
        'jun' => 'INT DEFAULT 0',
        'jul' => 'INT DEFAULT 0',
        'aug' => 'INT DEFAULT 0',
        'sep' => 'INT DEFAULT 0',
        'oct' => 'INT DEFAULT 0',
        'nov' => 'INT DEFAULT 0',
        'dec' => 'INT DEFAULT 0'
    ];

    foreach ($tables as $table) {
        echo "Checking table: $table\n";
        // Get existing columns
        $stmt = $pdo->query("PRAGMA table_info($table)");
        $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);

        foreach ($columns as $colName => $colDef) {
            if (!in_array($colName, $existingColumns)) {
                $sql = "ALTER TABLE $table ADD COLUMN $colName $colDef";
                $pdo->exec($sql);
                echo "  Added column: $colName\n";
            }
        }
    }

    echo "Database schema update completed successfully.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
