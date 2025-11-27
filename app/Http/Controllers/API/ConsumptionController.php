<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PDO;
use PDOException;

class ConsumptionController extends Controller
{
    private function getDbConnection()
    {
        try {
            $dbPath = database_path('database.sqlite');
            $pdo = new PDO('sqlite:' . $dbPath);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function getConsumptionData(Request $request)
    {
        $interval = $request->input('interval', '1D');

        $conn = $this->getDbConnection();

        if (!$conn) {
            return response()->json(['error' => 'Database connection failed'], 500);
        }

        try {
            $loads = ['light_loads', 'medium_loads', 'heavy_loads', 'universal_loads'];
            $aggregatedData = [
                'light' => 0,
                'medium' => 0,
                'heavy' => 0,
                'universal' => 0,
            ];

            $field = $this->getFieldForInterval($interval);

            foreach ($loads as $index => $table) {
                $query = "SELECT SUM({$field}) as total FROM {$table}";
                $stmt = $conn->prepare($query);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);

                $loadType = ['light', 'medium', 'heavy', 'universal'][$index];
                $aggregatedData[$loadType] = (int) ($result['total'] ?? 0);
            }

            return response()->json([
                'interval' => $interval,
                'data' => $aggregatedData,
                'timestamp' => now()->toIso8601String()
            ]);
        } catch (PDOException $e) {
            return response()->json(['error' => 'Query failed: ' . $e->getMessage()], 500);
        }
    }

    public function syncFirebaseData(Request $request)
    {
        $load_type = $request->input('load_type');
        $socket_id = $request->input('socket_id');
        $power = $request->input('power', 0);
        $duration_seconds = $request->input('duration_seconds', 60);

        if (!$load_type || !$socket_id) {
            return response()->json(['error' => 'Missing required fields'], 400);
        }

        $table = $this->getTableForLoadType($load_type);
        if (!$table) {
            return response()->json(['error' => 'Invalid load type'], 400);
        }

        $conn = $this->getDbConnection();

        if (!$conn) {
            return response()->json(['error' => 'Database connection failed'], 500);
        }

        try {
            $watt_hours = ($power * $duration_seconds) / 3600;
            $wh_int = (int) ($watt_hours * 1000);

            $hourBucket = $this->getCurrentTimeBucket();
            $dayBucket = $this->getCurrentDayBucket();
            $weekBucket = $this->getCurrentWeekBucket();
            $monthBucket = $this->getCurrentMonthBucket();

            $stmt = $conn->prepare("
                UPDATE {$table}
                SET
                    {$hourBucket} = {$hourBucket} + ?,
                    {$dayBucket} = {$dayBucket} + ?,
                    {$weekBucket} = {$weekBucket} + ?,
                    {$monthBucket} = {$monthBucket} + ?
                WHERE socket_id = ?
            ");

            $stmt->execute([$wh_int, $wh_int, $wh_int, $wh_int, $socket_id]);

            return response()->json([
                'success' => true,
                'message' => 'Consumption updated',
                'watt_hours' => $watt_hours,
                'buckets' => [
                    'hour' => $hourBucket,
                    'day' => $dayBucket,
                    'week' => $weekBucket,
                    'month' => $monthBucket
                ]
            ]);
        } catch (PDOException $e) {
            return response()->json(['error' => 'Update failed: ' . $e->getMessage()], 500);
        }
    }

    public function getConsumptionHistory(Request $request)
    {
        $interval = $request->input('interval', '1D');

        $conn = $this->getDbConnection();

        if (!$conn) {
            return response()->json(['error' => 'Database connection failed'], 500);
        }

        try {
            $loads = ['light_loads', 'medium_loads', 'heavy_loads', 'universal_loads'];
            $loadTypes = ['light', 'medium', 'heavy', 'universal'];

            $fields = $this->getFieldsForInterval($interval);
            $timeLabels = $this->getTimeLabelsForInterval($interval);

            $chartData = [];

            foreach ($loadTypes as $index => $loadType) {
                $table = $loads[$index];
                $chartData[$loadType] = [];

                foreach ($fields as $i => $field) {
                    $query = "SELECT SUM({$field}) as total FROM {$table}";
                    $stmt = $conn->prepare($query);
                    $stmt->execute();
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);

                    $value = (int) ($result['total'] ?? 0);

                    $chartData[$loadType][] = [
                        'time' => $timeLabels[$i],
                        'value' => $value
                    ];
                }
            }

            return response()->json([
                'interval' => $interval,
                'data' => $chartData
            ]);
        } catch (PDOException $e) {
            return response()->json(['error' => 'Query failed: ' . $e->getMessage()], 500);
        }
    }

    private function getCurrentTimeBucket()
    {
        $hour = (int) date('H');
        if ($hour < 4)
            return 'h4';
        if ($hour < 8)
            return 'h8';
        if ($hour < 12)
            return 'h12';
        if ($hour < 16)
            return 'h16';
        if ($hour < 20)
            return 'h20';
        return 'h24';
    }

    private function getCurrentDayBucket()
    {
        return strtolower(date('D'));
    }

    private function getCurrentWeekBucket()
    {
        $day = (int) date('j');
        if ($day <= 7)
            return 'week1';
        if ($day <= 14)
            return 'week2';
        if ($day <= 21)
            return 'week3';
        return 'week4';
    }

    private function getCurrentMonthBucket()
    {
        return strtolower(date('M'));
    }

    private function getFieldsForInterval($interval)
    {
        switch ($interval) {
            case '1D':
                return ['h4', 'h8', 'h12', 'h16', 'h20', 'h24'];
            case '1W':
                return ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
            case '1M':
                return ['week1', 'week2', 'week3', 'week4'];
            case '1Y':
                return ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];
            default:
                return ['h4', 'h8', 'h12', 'h16', 'h20', 'h24'];
        }
    }

    private function getTimeLabelsForInterval($interval)
    {
        switch ($interval) {
            case '1D':
                return ['0h', '4h', '8h', '12h', '16h', '20h'];
            case '1W':
                return ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
            case '1M':
                return ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
            case '1Y':
                return ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            default:
                return ['0h', '4h', '8h', '12h', '16h', '20h'];
        }
    }

    private function getFieldForInterval($interval)
    {
        switch ($interval) {
            case '1D':
                return 'eu_daily';
            case '1W':
            case '1M':
                return 'eu_monthly';
            case '1Y':
                return 'eu_monthly';
            default:
                return 'eu_daily';
        }
    }

    private function getTableForLoadType($load_type)
    {
        $tables = [
            'light' => 'light_loads',
            'medium' => 'medium_loads',
            'heavy' => 'heavy_loads',
            'universal' => 'universal_loads'
        ];

        return $tables[$load_type] ?? null;
    }

    public function checkReset(Request $request)
    {
        $conn = $this->getDbConnection();

        if (!$conn) {
            return response()->json(['error' => 'Database connection failed'], 500);
        }

        try {
            $checkTable = $conn->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name='reset_logs'");
            $checkTable->execute();
            $tableExists = $checkTable->fetch();

            if (!$tableExists) {
                $sql = "CREATE TABLE reset_logs (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    reset_type VARCHAR(50) UNIQUE NOT NULL,
                    last_reset_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )";
                $conn->exec($sql);

                $now = date('Y-m-d H:i:s');
                $stmt = $conn->prepare("INSERT INTO reset_logs (reset_type, last_reset_at) VALUES (?, ?)");
                foreach (['daily', 'weekly', 'monthly', 'yearly'] as $type) {
                    $stmt->execute([$type, $now]);
                }
            }

            $resetsPerformed = [];
            $errors = [];

            $todayStart = strtotime('today midnight');
            if ($this->shouldReset($conn, 'daily', $todayStart)) {
                if ($this->performDailyReset($conn)) {
                    $this->updateLastReset($conn, 'daily');
                    $resetsPerformed[] = 'daily';
                } else {
                    $errors[] = 'Daily reset failed';
                }
            }

            $weekStart = strtotime('monday this week midnight');
            if ($this->shouldReset($conn, 'weekly', $weekStart)) {
                if ($this->performWeeklyReset($conn)) {
                    $this->updateLastReset($conn, 'weekly');
                    $resetsPerformed[] = 'weekly';
                } else {
                    $errors[] = 'Weekly reset failed';
                }
            }

            $monthStart = strtotime('first day of this month midnight');
            if ($this->shouldReset($conn, 'monthly', $monthStart)) {
                if ($this->performMonthlyReset($conn)) {
                    $this->updateLastReset($conn, 'monthly');
                    $resetsPerformed[] = 'monthly';
                } else {
                    $errors[] = 'Monthly reset failed';
                }
            }

            $yearStart = strtotime('first day of january this year midnight');
            if ($this->shouldReset($conn, 'yearly', $yearStart)) {
                if ($this->performYearlyReset($conn)) {
                    $this->updateLastReset($conn, 'yearly');
                    $resetsPerformed[] = 'yearly';
                } else {
                    $errors[] = 'Yearly reset failed';
                }
            }

            return response()->json([
                'success' => true,
                'resets_performed' => $resetsPerformed,
                'errors' => $errors,
                'timestamp' => now()->toIso8601String()
            ]);
        } catch (PDOException $e) {
            return response()->json(['error' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    private function shouldReset($conn, $type, $thresholdTimestamp)
    {
        try {
            $stmt = $conn->prepare("SELECT last_reset_at FROM reset_logs WHERE reset_type = ?");
            $stmt->execute([$type]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                $lastReset = strtotime($result['last_reset_at']);
                return $lastReset < $thresholdTimestamp;
            }
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    private function updateLastReset($conn, $type)
    {
        try {
            $now = date('Y-m-d H:i:s');
            $stmt = $conn->prepare("UPDATE reset_logs SET last_reset_at = ? WHERE reset_type = ?");
            $stmt->execute([$now, $type]);
        } catch (PDOException $e) {
            return false;
        }
    }

    private function performDailyReset($conn)
    {
        try {
            $tables = ['light_loads', 'medium_loads', 'heavy_loads', 'universal_loads'];
            foreach ($tables as $table) {
                $sql = "UPDATE {$table} SET
                    h4 = 0, h8 = 0, h12 = 0, h16 = 0, h20 = 0, h24 = 0,
                    eu_daily = 0, ec_daily = 0";
                $conn->exec($sql);
            }
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    private function performWeeklyReset($conn)
    {
        try {
            $tables = ['light_loads', 'medium_loads', 'heavy_loads', 'universal_loads'];
            foreach ($tables as $table) {
                $sql = "UPDATE {$table} SET
                    mon = 0, tue = 0, wed = 0, thu = 0, fri = 0, sat = 0, sun = 0";
                $conn->exec($sql);
            }
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    private function performMonthlyReset($conn)
    {
        try {
            $tables = ['light_loads', 'medium_loads', 'heavy_loads', 'universal_loads'];
            foreach ($tables as $table) {
                $sql = "UPDATE {$table} SET
                    week1 = 0, week2 = 0, week3 = 0, week4 = 0,
                    eu_monthly = 0, ec_monthly = 0";
                $conn->exec($sql);
            }
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    private function performYearlyReset($conn)
    {
        try {
            $tables = ['light_loads', 'medium_loads', 'heavy_loads', 'universal_loads'];
            foreach ($tables as $table) {
                $sql = "UPDATE {$table} SET
                    jan = 0, feb = 0, mar = 0, apr = 0, may = 0, jun = 0,
                    jul = 0, aug = 0, sep = 0, oct = 0, nov = 0, dec = 0";
                $conn->exec($sql);
            }
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
}
