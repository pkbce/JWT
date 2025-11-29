<?php
try {
    $pdo = new PDO('sqlite:database/database.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $tables = [
        "CREATE TABLE IF NOT EXISTS light_loads (
            socket_name TEXT,
            socket_id TEXT,
            power_status INT,
            eu_daily INT,
            ec_daily INT,
            eu_monthly INT,
            ec_monthly INT
        )",
        "CREATE TABLE IF NOT EXISTS medium_loads (
            socket_name TEXT,
            socket_id TEXT,
            power_status INT,
            eu_daily INT,
            ec_daily INT,
            eu_monthly INT,
            ec_monthly INT
        )",
        "CREATE TABLE IF NOT EXISTS heavy_loads (
            socket_name TEXT,
            socket_id TEXT,
            power_status INT,
            eu_daily INT,
            ec_daily INT,
            eu_monthly INT,
            ec_monthly INT
        )",
        "CREATE TABLE IF NOT EXISTS universal_loads (
            socket_name TEXT,
            socket_id TEXT,
            power_status INT,
            eu_daily INT,
            ec_daily INT,
            eu_monthly INT,
            ec_monthly INT
        )",
        "CREATE TABLE IF NOT EXISTS reset_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            reset_type TEXT,
            last_reset_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )"
    ];

    foreach ($tables as $sql) {
        $pdo->exec($sql);
        echo "Executed: " . substr($sql, 0, 30) . "...\n";
    }

    // Check if tables are empty before inserting
    $stmt = $pdo->query("SELECT COUNT(*) FROM light_loads");
    if ($stmt->fetchColumn() == 0) {
        $inserts = [
            "INSERT INTO light_loads (socket_name, socket_id, power_status, eu_daily, ec_daily, eu_monthly, ec_monthly) VALUES ('Socket 1', 'ESP1', 0, 0, 0, 0, 0)",
            "INSERT INTO medium_loads (socket_name, socket_id, power_status, eu_daily, ec_daily, eu_monthly, ec_monthly) VALUES ('Socket 2', 'ESP2', 0, 0, 0, 0, 0)",
            "INSERT INTO heavy_loads (socket_name, socket_id, power_status, eu_daily, ec_daily, eu_monthly, ec_monthly) VALUES ('Socket 3', 'ESP3', 0, 0, 0, 0, 0)",
            "INSERT INTO universal_loads (socket_name, socket_id, power_status, eu_daily, ec_daily, eu_monthly, ec_monthly) VALUES ('Socket 4', 'ESP4', 0, 0, 0, 0, 0)"
        ];

        foreach ($inserts as $sql) {
            $pdo->exec($sql);
            echo "Inserted row.\n";
        }
    } else {
        echo "Tables already have data, skipping inserts.\n";
    }

    echo "Database setup completed successfully.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
