<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use mysqli;

class ConsumptionController extends Controller
{
    /**
     * Get aggregated consumption data for charts
     * Aggregates data from all four load tables
     */
    public function getConsumptionData(Request $request)
    {
        $db_name = $request->input('name');
        $interval = $request->input('interval', '1D'); // 1D, 1W, 1M, 1Y

        if (!$db_name) {
            return response()->json(['error' => 'Database name is required'], 400);
        }

        $conn = new mysqli("localhost", "root", "", $db_name);

        if ($conn->connect_error) {
            return response()->json(['error' => 'Connection failed: ' . $conn->connect_error], 500);
        }

        // Get data from all four tables
        $loads = ['light_loads', 'medium_loads', 'heavy_loads', 'universal_loads'];
        $aggregatedData = [
            'light' => 0,
            'medium' => 0,
            'heavy' => 0,
            'universal' => 0,
        ];

        $field = $this->getFieldForInterval($interval);

        foreach ($loads as $index => $table) {
            $query = "SELECT SUM({$field}) as total FROM `{$table}`";
            $result = $conn->query($query);
            
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $loadType = ['light', 'medium', 'heavy', 'universal'][$index];
                $aggregatedData[$loadType] = (int)($row['total'] ?? 0);
            }
        }

        $conn->close();

        return response()->json([
            'interval' => $interval,
            'data' => $aggregatedData,
            'timestamp' => now()->toIso8601String()
        ]);
    }

    /**
     * Sync Firebase data to database
     * Receives power readings from Firebase and updates consumption
     */
    public function syncFirebaseData(Request $request)
    {
        $db_name = $request->input('name');
        $load_type = $request->input('load_type'); // 'light', 'medium', 'heavy', 'universal'
        $socket_id = $request->input('socket_id');
        $power = $request->input('power', 0);
        $duration_seconds = $request->input('duration_seconds', 60); // How long this reading lasted

        if (!$db_name || !$load_type || !$socket_id) {
            return response()->json(['error' => 'Missing required fields'], 400);
        }

        $table = $this->getTableForLoadType($load_type);
        if (!$table) {
            return response()->json(['error' => 'Invalid load type'], 400);
        }

        $conn = new mysqli("localhost", "root", "", $db_name);

        if ($conn->connect_error) {
            return response()->json(['error' => 'Connection failed: ' . $conn->connect_error], 500);
        }

        // Calculate energy consumed in this period (Watt-seconds to Watt-hours)
        $watt_hours = ($power * $duration_seconds) / 3600;
        $wh_int = (int)($watt_hours * 1000); // Store as integer with precision
        
        // Get current time buckets
        $hourBucket = $this->getCurrentTimeBucket();
        $dayBucket = $this->getCurrentDayBucket();
        $weekBucket = $this->getCurrentWeekBucket();
        $monthBucket = $this->getCurrentMonthBucket();
        
        // Update all time buckets
        $stmt = $conn->prepare("
            UPDATE `{$table}` 
            SET 
                `{$hourBucket}` = `{$hourBucket}` + ?,
                `{$dayBucket}` = `{$dayBucket}` + ?,
                `{$weekBucket}` = `{$weekBucket}` + ?,
                `{$monthBucket}` = `{$monthBucket}` + ?
            WHERE `socket_id` = ?
        ");

        if (!$stmt) {
            $conn->close();
            return response()->json(['error' => 'Prepare failed: ' . $conn->error], 500);
        }

        $stmt->bind_param("iiiis", $wh_int, $wh_int, $wh_int, $wh_int, $socket_id);

        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
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
        } else {
            $error = $stmt->error;
            $stmt->close();
            $conn->close();
            return response()->json(['error' => 'Update failed: ' . $error], 500);
        }
    }

    /**
     * Get consumption history for charts with time buckets
     */
    public function getConsumptionHistory(Request $request)
    {
        $db_name = $request->input('name');
        $interval = $request->input('interval', '1D');

        if (!$db_name) {
            return response()->json(['error' => 'Database name is required'], 400);
        }

        $conn = new mysqli("localhost", "root", "", $db_name);

        if ($conn->connect_error) {
            return response()->json(['error' => 'Connection failed: ' . $conn->connect_error], 500);
        }

        $loads = ['light_loads', 'medium_loads', 'heavy_loads', 'universal_loads'];
        $loadTypes = ['light', 'medium', 'heavy', 'universal'];
        
        $fields = $this->getFieldsForInterval($interval);
        $timeLabels = $this->getTimeLabelsForInterval($interval);
        
        // Build chart data structure
        $chartData = [];
        
        foreach ($loadTypes as $index => $loadType) {
            $table = $loads[$index];
            $chartData[$loadType] = [];
            
            foreach ($fields as $i => $field) {
                $query = "SELECT SUM(`{$field}`) as total FROM `{$table}`";
                $result = $conn->query($query);
                
                $value = 0;
                if ($result && $result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $value = (int)($row['total'] ?? 0);
                }
                
                $chartData[$loadType][] = [
                    'time' => $timeLabels[$i],
                    'value' => $value
                ];
            }
        }

        $conn->close();

        return response()->json([
            'interval' => $interval,
            'data' => $chartData
        ]);
    }

    /**
     * Get current 4-hour time bucket
     */
    private function getCurrentTimeBucket()
    {
        $hour = (int)date('H');
        if ($hour < 4) return 'h4';
        if ($hour < 8) return 'h8';
        if ($hour < 12) return 'h12';
        if ($hour < 16) return 'h16';
        if ($hour < 20) return 'h20';
        return 'h24';
    }

    /**
     * Get current day bucket
     */
    private function getCurrentDayBucket()
    {
        return strtolower(date('D')); // mon, tue, wed, thu, fri, sat, sun
    }

    /**
     * Get current week bucket (1-4)
     */
    private function getCurrentWeekBucket()
    {
        $day = (int)date('j'); // Day of month
        if ($day <= 7) return 'week1';
        if ($day <= 14) return 'week2';
        if ($day <= 21) return 'week3';
        return 'week4'; // Days 22-31
    }

    /**
     * Get current month bucket
     */
    private function getCurrentMonthBucket()
    {
        return strtolower(date('M')); // jan, feb, mar, etc.
    }

    /**
     * Get field names for interval
     */
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

    /**
     * Get time labels for interval
     */
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

    /**
     * Helper: Get database field name based on interval (legacy)
     */
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

    /**
     * Helper: Get table name from load type
     */
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
}
