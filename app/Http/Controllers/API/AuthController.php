<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use PDO;
use PDOException;
use Exception;

class AuthController extends Controller
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

    // Register new user
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        return response()->json(['message' => 'User registered successfully', 'user' => $user], 201);
    }

    public function create_db(Request $request)
    {
        $db_name = auth('api')->user()->name;
        $conn = $this->getDbConnection();

        if (!$conn) {
            return response()->json(['error' => 'Database connection failed'], 500);
        }

        try {
            // Create tables for the user database
            $tables = [
                "CREATE TABLE IF NOT EXISTS light_loads (
                    socket_name TEXT,
                    socket_id TEXT PRIMARY KEY,
                    power_status INT DEFAULT 0,
                    eu_daily INT DEFAULT 0,
                    ec_daily INT DEFAULT 0,
                    eu_monthly INT DEFAULT 0,
                    ec_monthly INT DEFAULT 0
                )",
                "CREATE TABLE IF NOT EXISTS medium_loads (
                    socket_name TEXT,
                    socket_id TEXT PRIMARY KEY,
                    power_status INT DEFAULT 0,
                    eu_daily INT DEFAULT 0,
                    ec_daily INT DEFAULT 0,
                    eu_monthly INT DEFAULT 0,
                    ec_monthly INT DEFAULT 0
                )",
                "CREATE TABLE IF NOT EXISTS heavy_loads (
                    socket_name TEXT,
                    socket_id TEXT PRIMARY KEY,
                    power_status INT DEFAULT 0,
                    eu_daily INT DEFAULT 0,
                    ec_daily INT DEFAULT 0,
                    eu_monthly INT DEFAULT 0,
                    ec_monthly INT DEFAULT 0
                )",
                "CREATE TABLE IF NOT EXISTS universal_loads (
                    socket_name TEXT,
                    socket_id TEXT PRIMARY KEY,
                    power_status INT DEFAULT 0,
                    eu_daily INT DEFAULT 0,
                    ec_daily INT DEFAULT 0,
                    eu_monthly INT DEFAULT 0,
                    ec_monthly INT DEFAULT 0
                )"
            ];

            foreach ($tables as $sql) {
                $conn->exec($sql);
            }

            return response()->json(['message' => 'Database and tables created successfully'], 200);
        } catch (PDOException $e) {
            return response()->json(['error' => 'Error creating tables: ' . $e->getMessage()], 500);
        }
    }

    // Login user and return JWT token
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = auth('api')->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    // Get user profile
    public function profile()
    {
        return response()->json(auth('api')->user());
    }

    // Logout user (invalidate token)
    public function logout()
    {
        auth('api')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    // Refresh JWT token
    public function refresh()
    {
        return $this->respondWithToken(auth('api')->refresh());
    }

    // Return token response structure
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => null,
        ]);
    }

    // Database routing
    public function ll_db_route(Request $request)
    {
        $conn = $this->getDbConnection();

        if (!$conn) {
            return response()->json(['error' => 'Database connection failed'], 500);
        }

        try {
            $stmt = $conn->prepare("SELECT * FROM light_loads");
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return response()->json($data);
        } catch (PDOException $e) {
            return response()->json(['error' => 'Query failed: ' . $e->getMessage()], 500);
        }
    }

    public function ml_db_route(Request $request)
    {
        $conn = $this->getDbConnection();

        if (!$conn) {
            return response()->json(['error' => 'Database connection failed'], 500);
        }

        try {
            $stmt = $conn->prepare("SELECT * FROM medium_loads");
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return response()->json($data);
        } catch (PDOException $e) {
            return response()->json(['error' => 'Query failed: ' . $e->getMessage()], 500);
        }
    }

    public function hl_db_route(Request $request)
    {
        $conn = $this->getDbConnection();

        if (!$conn) {
            return response()->json(['error' => 'Database connection failed'], 500);
        }

        try {
            $stmt = $conn->prepare("SELECT * FROM heavy_loads");
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return response()->json($data);
        } catch (PDOException $e) {
            return response()->json(['error' => 'Query failed: ' . $e->getMessage()], 500);
        }
    }

    public function ul_db_route(Request $request)
    {
        $conn = $this->getDbConnection();

        if (!$conn) {
            return response()->json(['error' => 'Database connection failed'], 500);
        }

        try {
            $stmt = $conn->prepare("SELECT * FROM universal_loads");
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return response()->json($data);
        } catch (PDOException $e) {
            return response()->json(['error' => 'Query failed: ' . $e->getMessage()], 500);
        }
    }

    // Power status updates
    public function ll_change_power_status(Request $request)
    {
        $socket_id = $request->input('socket_id');
        $power_status = $request->input('power_status');
        $conn = $this->getDbConnection();

        if (!$conn) {
            return response()->json(['error' => 'Database connection failed'], 500);
        }

        try {
            $stmt = $conn->prepare("UPDATE light_loads SET power_status = ? WHERE socket_id = ?");
            $stmt->execute([$power_status, $socket_id]);
            return response()->json(['success' => true, 'message' => 'Power status updated']);
        } catch (PDOException $e) {
            return response()->json(['error' => 'Update failed: ' . $e->getMessage()], 500);
        }
    }

    public function ml_change_power_status(Request $request)
    {
        $socket_id = $request->input('socket_id');
        $power_status = $request->input('power_status');
        $conn = $this->getDbConnection();

        if (!$conn) {
            return response()->json(['error' => 'Database connection failed'], 500);
        }

        try {
            $stmt = $conn->prepare("UPDATE medium_loads SET power_status = ? WHERE socket_id = ?");
            $stmt->execute([$power_status, $socket_id]);
            return response()->json(['success' => true, 'message' => 'Power status updated']);
        } catch (PDOException $e) {
            return response()->json(['error' => 'Update failed: ' . $e->getMessage()], 500);
        }
    }

    public function hl_change_power_status(Request $request)
    {
        $socket_id = $request->input('socket_id');
        $power_status = $request->input('power_status');
        $conn = $this->getDbConnection();

        if (!$conn) {
            return response()->json(['error' => 'Database connection failed'], 500);
        }

        try {
            $stmt = $conn->prepare("UPDATE heavy_loads SET power_status = ? WHERE socket_id = ?");
            $stmt->execute([$power_status, $socket_id]);
            return response()->json(['success' => true, 'message' => 'Power status updated']);
        } catch (PDOException $e) {
            return response()->json(['error' => 'Update failed: ' . $e->getMessage()], 500);
        }
    }

    public function ul_change_power_status(Request $request)
    {
        $socket_id = $request->input('socket_id');
        $power_status = $request->input('power_status');
        $conn = $this->getDbConnection();

        if (!$conn) {
            return response()->json(['error' => 'Database connection failed'], 500);
        }

        try {
            $stmt = $conn->prepare("UPDATE universal_loads SET power_status = ? WHERE socket_id = ?");
            $stmt->execute([$power_status, $socket_id]);
            return response()->json(['success' => true, 'message' => 'Power status updated']);
        } catch (PDOException $e) {
            return response()->json(['error' => 'Update failed: ' . $e->getMessage()], 500);
        }
    }

    // Row deletion
    public function ll_delete_row(Request $request)
    {
        $socket_id = $request->input('socket_id');
        $conn = $this->getDbConnection();

        if (!$conn) {
            return response()->json(['error' => 'Database connection failed'], 500);
        }

        try {
            $stmt = $conn->prepare("DELETE FROM light_loads WHERE socket_id = ?");
            $stmt->execute([$socket_id]);
            return response()->json(['success' => true, 'message' => 'Row deleted successfully']);
        } catch (PDOException $e) {
            return response()->json(['error' => 'Delete failed: ' . $e->getMessage()], 500);
        }
    }

    public function ml_delete_row(Request $request)
    {
        $socket_id = $request->input('socket_id');
        $conn = $this->getDbConnection();

        if (!$conn) {
            return response()->json(['error' => 'Database connection failed'], 500);
        }

        try {
            $stmt = $conn->prepare("DELETE FROM medium_loads WHERE socket_id = ?");
            $stmt->execute([$socket_id]);
            return response()->json(['success' => true, 'message' => 'Row deleted successfully']);
        } catch (PDOException $e) {
            return response()->json(['error' => 'Delete failed: ' . $e->getMessage()], 500);
        }
    }

    public function hl_delete_row(Request $request)
    {
        $socket_id = $request->input('socket_id');
        $conn = $this->getDbConnection();

        if (!$conn) {
            return response()->json(['error' => 'Database connection failed'], 500);
        }

        try {
            $stmt = $conn->prepare("DELETE FROM heavy_loads WHERE socket_id = ?");
            $stmt->execute([$socket_id]);
            return response()->json(['success' => true, 'message' => 'Row deleted successfully']);
        } catch (PDOException $e) {
            return response()->json(['error' => 'Delete failed: ' . $e->getMessage()], 500);
        }
    }

    public function ul_delete_row(Request $request)
    {
        $socket_id = $request->input('socket_id');
        $conn = $this->getDbConnection();

        if (!$conn) {
            return response()->json(['error' => 'Database connection failed'], 500);
        }

        try {
            $stmt = $conn->prepare("DELETE FROM universal_loads WHERE socket_id = ?");
            $stmt->execute([$socket_id]);
            return response()->json(['success' => true, 'message' => 'Row deleted successfully']);
        } catch (PDOException $e) {
            return response()->json(['error' => 'Delete failed: ' . $e->getMessage()], 500);
        }
    }

    // Socket name changes
    public function ll_change_socket_name(Request $request)
    {
        $socket_id = $request->input('socket_id');
        $socket_name = $request->input('socket_name');
        $conn = $this->getDbConnection();

        if (!$conn) {
            return response()->json(['error' => 'Database connection failed'], 500);
        }

        try {
            $stmt = $conn->prepare("UPDATE light_loads SET socket_name = ? WHERE socket_id = ?");
            $stmt->execute([$socket_name, $socket_id]);
            return response()->json(['success' => true, 'message' => 'Socket name updated']);
        } catch (PDOException $e) {
            return response()->json(['error' => 'Update failed: ' . $e->getMessage()], 500);
        }
    }

    public function ml_change_socket_name(Request $request)
    {
        $socket_id = $request->input('socket_id');
        $socket_name = $request->input('socket_name');
        $conn = $this->getDbConnection();

        if (!$conn) {
            return response()->json(['error' => 'Database connection failed'], 500);
        }

        try {
            $stmt = $conn->prepare("UPDATE medium_loads SET socket_name = ? WHERE socket_id = ?");
            $stmt->execute([$socket_name, $socket_id]);
            return response()->json(['success' => true, 'message' => 'Socket name updated']);
        } catch (PDOException $e) {
            return response()->json(['error' => 'Update failed: ' . $e->getMessage()], 500);
        }
    }

    public function hl_change_socket_name(Request $request)
    {
        $socket_id = $request->input('socket_id');
        $socket_name = $request->input('socket_name');
        $conn = $this->getDbConnection();

        if (!$conn) {
            return response()->json(['error' => 'Database connection failed'], 500);
        }

        try {
            $stmt = $conn->prepare("UPDATE heavy_loads SET socket_name = ? WHERE socket_id = ?");
            $stmt->execute([$socket_name, $socket_id]);
            return response()->json(['success' => true, 'message' => 'Socket name updated']);
        } catch (PDOException $e) {
            return response()->json(['error' => 'Update failed: ' . $e->getMessage()], 500);
        }
    }

    public function ul_change_socket_name(Request $request)
    {
        $socket_id = $request->input('socket_id');
        $socket_name = $request->input('socket_name');
        $conn = $this->getDbConnection();

        if (!$conn) {
            return response()->json(['error' => 'Database connection failed'], 500);
        }

        try {
            $stmt = $conn->prepare("UPDATE universal_loads SET socket_name = ? WHERE socket_id = ?");
            $stmt->execute([$socket_name, $socket_id]);
            return response()->json(['success' => true, 'message' => 'Socket name updated']);
        } catch (PDOException $e) {
            return response()->json(['error' => 'Update failed: ' . $e->getMessage()], 500);
        }
    }

    // Add socket
    public function ll_add_socket(Request $request)
    {
        $socket_id = $request->input('socket_id');
        $socket_name = $request->input('socket_name');
        $conn = $this->getDbConnection();

        if (!$conn) {
            return response()->json(['error' => 'Database connection failed'], 500);
        }

        try {
            $stmt = $conn->prepare("INSERT INTO light_loads (socket_id, socket_name, power_status, eu_daily, ec_daily, eu_monthly, ec_monthly) VALUES (?, ?, 0, 0, 0, 0, 0)");
            $stmt->execute([$socket_id, $socket_name]);
            return response()->json(['success' => true, 'message' => 'Socket added successfully']);
        } catch (PDOException $e) {
            return response()->json(['error' => 'Insert failed: ' . $e->getMessage()], 500);
        }
    }

    public function ml_add_socket(Request $request)
    {
        $socket_id = $request->input('socket_id');
        $socket_name = $request->input('socket_name');
        $conn = $this->getDbConnection();

        if (!$conn) {
            return response()->json(['error' => 'Database connection failed'], 500);
        }

        try {
            $stmt = $conn->prepare("INSERT INTO medium_loads (socket_id, socket_name, power_status, eu_daily, ec_daily, eu_monthly, ec_monthly) VALUES (?, ?, 0, 0, 0, 0, 0)");
            $stmt->execute([$socket_id, $socket_name]);
            return response()->json(['success' => true, 'message' => 'Socket added successfully']);
        } catch (PDOException $e) {
            return response()->json(['error' => 'Insert failed: ' . $e->getMessage()], 500);
        }
    }

    public function hl_add_socket(Request $request)
    {
        $socket_id = $request->input('socket_id');
        $socket_name = $request->input('socket_name');
        $conn = $this->getDbConnection();

        if (!$conn) {
            return response()->json(['error' => 'Database connection failed'], 500);
        }

        try {
            $stmt = $conn->prepare("INSERT INTO heavy_loads (socket_id, socket_name, power_status, eu_daily, ec_daily, eu_monthly, ec_monthly) VALUES (?, ?, 0, 0, 0, 0, 0)");
            $stmt->execute([$socket_id, $socket_name]);
            return response()->json(['success' => true, 'message' => 'Socket added successfully']);
        } catch (PDOException $e) {
            return response()->json(['error' => 'Insert failed: ' . $e->getMessage()], 500);
        }
    }

    public function ul_add_socket(Request $request)
    {
        $socket_id = $request->input('socket_id');
        $socket_name = $request->input('socket_name');
        $conn = $this->getDbConnection();

        if (!$conn) {
            return response()->json(['error' => 'Database connection failed'], 500);
        }

        try {
            $stmt = $conn->prepare("INSERT INTO universal_loads (socket_id, socket_name, power_status, eu_daily, ec_daily, eu_monthly, ec_monthly) VALUES (?, ?, 0, 0, 0, 0, 0)");
            $stmt->execute([$socket_id, $socket_name]);
            return response()->json(['success' => true, 'message' => 'Socket added successfully']);
        } catch (PDOException $e) {
            return response()->json(['error' => 'Insert failed: ' . $e->getMessage()], 500);
        }
    }

    // Consumption updates
    public function ll_update_consumption(Request $request)
    {
        $socket_id = $request->input('socket_id');
        $eu_daily = $request->input('eu_daily');
        $ec_daily = $request->input('ec_daily');
        $eu_monthly = $request->input('eu_monthly');
        $ec_monthly = $request->input('ec_monthly');

        $conn = $this->getDbConnection();

        if (!$conn) {
            return response()->json(['error' => 'Database connection failed'], 500);
        }

        try {
            $stmt = $conn->prepare("UPDATE light_loads SET eu_daily = ?, ec_daily = ?, eu_monthly = ?, ec_monthly = ? WHERE socket_id = ?");
            $stmt->execute([$eu_daily, $ec_daily, $eu_monthly, $ec_monthly, $socket_id]);
            return response()->json(['success' => true]);
        } catch (PDOException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function ml_update_consumption(Request $request)
    {
        $socket_id = $request->input('socket_id');
        $eu_daily = $request->input('eu_daily');
        $ec_daily = $request->input('ec_daily');
        $eu_monthly = $request->input('eu_monthly');
        $ec_monthly = $request->input('ec_monthly');

        $conn = $this->getDbConnection();

        if (!$conn) {
            return response()->json(['error' => 'Database connection failed'], 500);
        }

        try {
            $stmt = $conn->prepare("UPDATE medium_loads SET eu_daily = ?, ec_daily = ?, eu_monthly = ?, ec_monthly = ? WHERE socket_id = ?");
            $stmt->execute([$eu_daily, $ec_daily, $eu_monthly, $ec_monthly, $socket_id]);
            return response()->json(['success' => true]);
        } catch (PDOException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function hl_update_consumption(Request $request)
    {
        $socket_id = $request->input('socket_id');
        $eu_daily = $request->input('eu_daily');
        $ec_daily = $request->input('ec_daily');
        $eu_monthly = $request->input('eu_monthly');
        $ec_monthly = $request->input('ec_monthly');

        $conn = $this->getDbConnection();

        if (!$conn) {
            return response()->json(['error' => 'Database connection failed'], 500);
        }

        try {
            $stmt = $conn->prepare("UPDATE heavy_loads SET eu_daily = ?, ec_daily = ?, eu_monthly = ?, ec_monthly = ? WHERE socket_id = ?");
            $stmt->execute([$eu_daily, $ec_daily, $eu_monthly, $ec_monthly, $socket_id]);
            return response()->json(['success' => true]);
        } catch (PDOException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function ul_update_consumption(Request $request)
    {
        $socket_id = $request->input('socket_id');
        $eu_daily = $request->input('eu_daily');
        $ec_daily = $request->input('ec_daily');
        $eu_monthly = $request->input('eu_monthly');
        $ec_monthly = $request->input('ec_monthly');

        $conn = $this->getDbConnection();

        if (!$conn) {
            return response()->json(['error' => 'Database connection failed'], 500);
        }

        try {
            $stmt = $conn->prepare("UPDATE universal_loads SET eu_daily = ?, ec_daily = ?, eu_monthly = ?, ec_monthly = ? WHERE socket_id = ?");
            $stmt->execute([$eu_daily, $ec_daily, $eu_monthly, $ec_monthly, $socket_id]);
            return response()->json(['success' => true]);
        } catch (PDOException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Reset consumption
    public function ll_reset_consumption(Request $request)
    {
        $socket_id = $request->input('socket_id');
        $conn = $this->getDbConnection();

        if (!$conn) {
            return response()->json(['error' => 'Database connection failed'], 500);
        }

        try {
            $stmt = $conn->prepare("UPDATE light_loads SET eu_daily = 0, ec_daily = 0, eu_monthly = 0, ec_monthly = 0 WHERE socket_id = ?");
            $stmt->execute([$socket_id]);
            return response()->json(['success' => true]);
        } catch (PDOException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function ml_reset_consumption(Request $request)
    {
        $socket_id = $request->input('socket_id');
        $conn = $this->getDbConnection();

        if (!$conn) {
            return response()->json(['error' => 'Database connection failed'], 500);
        }

        try {
            $stmt = $conn->prepare("UPDATE medium_loads SET eu_daily = 0, ec_daily = 0, eu_monthly = 0, ec_monthly = 0 WHERE socket_id = ?");
            $stmt->execute([$socket_id]);
            return response()->json(['success' => true]);
        } catch (PDOException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function hl_reset_consumption(Request $request)
    {
        $socket_id = $request->input('socket_id');
        $conn = $this->getDbConnection();

        if (!$conn) {
            return response()->json(['error' => 'Database connection failed'], 500);
        }

        try {
            $stmt = $conn->prepare("UPDATE heavy_loads SET eu_daily = 0, ec_daily = 0, eu_monthly = 0, ec_monthly = 0 WHERE socket_id = ?");
            $stmt->execute([$socket_id]);
            return response()->json(['success' => true]);
        } catch (PDOException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function ul_reset_consumption(Request $request)
    {
        $socket_id = $request->input('socket_id');
        $conn = $this->getDbConnection();

        if (!$conn) {
            return response()->json(['error' => 'Database connection failed'], 500);
        }

        try {
            $stmt = $conn->prepare("UPDATE universal_loads SET eu_daily = 0, ec_daily = 0, eu_monthly = 0, ec_monthly = 0 WHERE socket_id = ?");
            $stmt->execute([$socket_id]);
            return response()->json(['success' => true]);
        } catch (PDOException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Change username
    public function change_username(Request $request)
    {
        $new_username = $request->input('new_un');
        $user = auth('api')->user();

        if (!$new_username) {
            return response()->json(['error' => 'New username is required'], 400);
        }

        try {
            $user->update(['name' => $new_username]);
            return response()->json(['success' => true, 'message' => 'Username changed successfully'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to change username: ' . $e->getMessage()], 500);
        }
    }

    // Change password
    public function changePassword(Request $request)
    {
        $password = $request->input('password');
        $user = auth('api')->user();

        if (!$password) {
            return response()->json(['error' => 'Password is required'], 400);
        }

        if (strlen($password) < 6) {
            return response()->json(['error' => 'Password must be at least 6 characters'], 400);
        }

        try {
            $user->update(['password' => bcrypt($password)]);
            return response()->json(['success' => true, 'message' => 'Password changed successfully'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to change password'], 500);
        }
    }

    // Delete account
    public function deleteAccount(Request $request)
    {
        $password = $request->input('password');
        $user = auth('api')->user();

        if (!$password) {
            return response()->json(['error' => 'Password is required'], 400);
        }

        if (!Hash::check($password, $user->password)) {
            return response()->json(['error' => 'Invalid password'], 401);
        }

        try {
            // Delete user from database
            $user->delete();

            // Logout user
            auth('api')->logout();

            return response()->json(['success' => true, 'message' => 'Account deleted successfully'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to delete account'], 500);
        }
    }
}
