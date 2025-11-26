<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use mysqli;

class AuthController extends Controller
{
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
        $db_name = $request->input('name');
        $conn = new mysqli("localhost", "root", "");
        $sql = "CREATE DATABASE `{$db_name}`";
        if ($conn->query($sql) === TRUE) {
            echo "Database created successfully";

            $conn2 = new mysqli("localhost", "root", "", $db_name);

            $ll = "CREATE TABLE light_loads (
                socket_name TEXT,
                socket_id TEXT,
                power_status INT,
                eu_daily INT,
                ec_daily INT,
                eu_monthly INT,
                ec_monthly INT
                )";
            $ml = "CREATE TABLE medium_loads (
                socket_name TEXT,
                socket_id TEXT,
                power_status INT,
                eu_daily INT,
                ec_daily INT,
                eu_monthly INT,
                ec_monthly INT
                )";
            $hl = "CREATE TABLE heavy_loads (
                socket_name TEXT,
                socket_id TEXT,
                power_status INT,
                eu_daily INT,
                ec_daily INT,
                eu_monthly INT,
                ec_monthly INT
                )";
            $ul = "CREATE TABLE universal_loads (
                socket_name TEXT,
                socket_id TEXT,
                power_status INT,
                eu_daily INT,
                ec_daily INT,
                eu_monthly INT,
                ec_monthly INT
                )";

            if (
                $conn2->query($ll) === TRUE && $conn2->query($ml) === TRUE &&
                $conn2->query($hl) === TRUE && $conn2->query($ul) === TRUE
            ) {
                echo "Tables created successfully";
            } else {
                echo "Error creating tables" . $conn2->error;
            }
        } else {
            echo "Error creating database: " . $conn->error;
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

    //database routing
    public function ll_db_route(Request $request)
    {
        $db_name = $request->input('name');
        $conn = new mysqli("localhost", "root", "", $db_name);

        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $ll_q = "SELECT * FROM `light_loads`";
        $ll_r = $conn->query($ll_q);
        $ll_d = [];

        if ($ll_r && $ll_r->num_rows > 0) {
            while ($row = $ll_r->fetch_assoc()) {
                $ll_d[] = $row;
            }
        }

        return response()->json($ll_d);
    }

    public function ml_db_route(Request $request)
    {
        $db_name = $request->input('name');
        $conn = new mysqli("localhost", "root", "", $db_name);

        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $ll_q = "SELECT * FROM `medium_loads`";
        $ll_r = $conn->query($ll_q);
        $ll_d = [];

        if ($ll_r && $ll_r->num_rows > 0) {
            while ($row = $ll_r->fetch_assoc()) {
                $ll_d[] = $row;
            }
        }

        return response()->json($ll_d);
    }

    public function hl_db_route(Request $request)
    {
        $db_name = $request->input('name');
        $conn = new mysqli("localhost", "root", "", $db_name);

        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $ll_q = "SELECT * FROM `heavy_loads`";
        $ll_r = $conn->query($ll_q);
        $ll_d = [];

        if ($ll_r && $ll_r->num_rows > 0) {
            while ($row = $ll_r->fetch_assoc()) {
                $ll_d[] = $row;
            }
        }

        return response()->json($ll_d);
    }

    public function ul_db_route(Request $request)
    {
        $db_name = $request->input('name');
        $conn = new mysqli("localhost", "root", "", $db_name);

        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $ll_q = "SELECT * FROM `universal_loads`";
        $ll_r = $conn->query($ll_q);
        $ll_d = [];

        if ($ll_r && $ll_r->num_rows > 0) {
            while ($row = $ll_r->fetch_assoc()) {
                $ll_d[] = $row;
            }
        }

        return response()->json($ll_d);
    }


    //power status
    public function ll_change_power_status(Request $request)
    {
        $db_name = $request->input('name');
        $socket_id = $request->input('socket_id');
        $power_status = $request->input('power_status');
        $conn = new mysqli("localhost", "root", "", $db_name);
        if ($conn->connect_error) {
            return response()->json(['error' => 'Connection failed: ' . $conn->connect_error], 500);
        }

        $stmt = $conn->prepare("UPDATE `light_loads` SET `power_status` = ? WHERE `socket_id` = ?");
        if (!$stmt) {
            return response()->json(['error' => 'Prepare failed: ' . $conn->error], 500);
        }
        $stmt->bind_param("is", $power_status, $socket_id);
        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            return response()->json(['success' => true, 'message' => 'Power status updated']);
        } else {
            $error = $stmt->error;
            $stmt->close();
            $conn->close();
            return response()->json(['error' => 'Update failed: ' . $error], 500);
        }
    }

    public function ml_change_power_status(Request $request)
    {
        $db_name = $request->input('name');
        $socket_id = $request->input('socket_id');
        $power_status = $request->input('power_status');
        $conn = new mysqli("localhost", "root", "", $db_name);
        if ($conn->connect_error) {
            return response()->json(['error' => 'Connection failed: ' . $conn->connect_error], 500);
        }

        $stmt = $conn->prepare("UPDATE `medium_loads` SET `power_status` = ? WHERE `socket_id` = ?");
        if (!$stmt) {
            return response()->json(['error' => 'Prepare failed: ' . $conn->error], 500);
        }
        $stmt->bind_param("is", $power_status, $socket_id);
        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            return response()->json(['success' => true, 'message' => 'Power status updated']);
        } else {
            $error = $stmt->error;
            $stmt->close();
            $conn->close();
            return response()->json(['error' => 'Update failed: ' . $error], 500);
        }
    }

    public function hl_change_power_status(Request $request)
    {
        $db_name = $request->input('name');
        $socket_id = $request->input('socket_id');
        $power_status = $request->input('power_status');
        $conn = new mysqli("localhost", "root", "", $db_name);
        if ($conn->connect_error) {
            return response()->json(['error' => 'Connection failed: ' . $conn->connect_error], 500);
        }

        $stmt = $conn->prepare("UPDATE `heavy_loads` SET `power_status` = ? WHERE `socket_id` = ?");
        if (!$stmt) {
            return response()->json(['error' => 'Prepare failed: ' . $conn->error], 500);
        }
        $stmt->bind_param("is", $power_status, $socket_id);
        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            return response()->json(['success' => true, 'message' => 'Power status updated']);
        } else {
            $error = $stmt->error;
            $stmt->close();
            $conn->close();
            return response()->json(['error' => 'Update failed: ' . $error], 500);
        }
    }

    public function ul_change_power_status(Request $request)
    {
        $db_name = $request->input('name');
        $socket_id = $request->input('socket_id');
        $power_status = $request->input('power_status');
        $conn = new mysqli("localhost", "root", "", $db_name);
        if ($conn->connect_error) {
            return response()->json(['error' => 'Connection failed: ' . $conn->connect_error], 500);
        }

        $stmt = $conn->prepare("UPDATE `universal_loads` SET `power_status` = ? WHERE `socket_id` = ?");
        if (!$stmt) {
            return response()->json(['error' => 'Prepare failed: ' . $conn->error], 500);
        }
        $stmt->bind_param("is", $power_status, $socket_id);
        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            return response()->json(['success' => true, 'message' => 'Power status updated']);
        } else {
            $error = $stmt->error;
            $stmt->close();
            $conn->close();
            return response()->json(['error' => 'Update failed: ' . $error], 500);
        }
    }


    //row deletion
    public function ll_delete_row(Request $request)
    {
        $db_name = $request->input('name');
        $socket_id = $request->input('socket_id');
        $conn = new mysqli("localhost", "root", "", $db_name);
        if ($conn->connect_error) {
            return response()->json(['error' => 'Connection failed: ' . $conn->connect_error], 500);
        }
        $stmt = $conn->prepare("DELETE FROM `light_loads` WHERE `socket_id` = ?");
        if (!$stmt) {
            return response()->json(['error' => 'Prepare failed: ' . $conn->error], 500);
        }

        $stmt->bind_param("s", $socket_id);
        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            return response()->json(['success' => true, 'message' => 'Row deleted successfully']);
        } else {
            $error = $stmt->error;
            $stmt->close();
            $conn->close();
            return response()->json(['error' => 'Delete failed: ' . $error], 500);
        }
    }

    public function ml_delete_row(Request $request)
    {
        $db_name = $request->input('name');
        $socket_id = $request->input('socket_id');
        $conn = new mysqli("localhost", "root", "", $db_name);
        if ($conn->connect_error) {
            return response()->json(['error' => 'Connection failed: ' . $conn->connect_error], 500);
        }
        $stmt = $conn->prepare("DELETE FROM `medium_loads` WHERE `socket_id` = ?");
        if (!$stmt) {
            return response()->json(['error' => 'Prepare failed: ' . $conn->error], 500);
        }

        $stmt->bind_param("s", $socket_id);
        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            return response()->json(['success' => true, 'message' => 'Row deleted successfully']);
        } else {
            $error = $stmt->error;
            $stmt->close();
            $conn->close();
            return response()->json(['error' => 'Delete failed: ' . $error], 500);
        }
    }

    public function hl_delete_row(Request $request)
    {
        $db_name = $request->input('name');
        $socket_id = $request->input('socket_id');
        $conn = new mysqli("localhost", "root", "", $db_name);
        if ($conn->connect_error) {
            return response()->json(['error' => 'Connection failed: ' . $conn->connect_error], 500);
        }
        $stmt = $conn->prepare("DELETE FROM `heavy_loads` WHERE `socket_id` = ?");
        if (!$stmt) {
            return response()->json(['error' => 'Prepare failed: ' . $conn->error], 500);
        }

        $stmt->bind_param("s", $socket_id);
        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            return response()->json(['success' => true, 'message' => 'Row deleted successfully']);
        } else {
            $error = $stmt->error;
            $stmt->close();
            $conn->close();
            return response()->json(['error' => 'Delete failed: ' . $error], 500);
        }
    }

    public function ul_delete_row(Request $request)
    {
        $db_name = $request->input('name');
        $socket_id = $request->input('socket_id');
        $conn = new mysqli("localhost", "root", "", $db_name);
        if ($conn->connect_error) {
            return response()->json(['error' => 'Connection failed: ' . $conn->connect_error], 500);
        }
        $stmt = $conn->prepare("DELETE FROM `universal_loads` WHERE `socket_id` = ?");
        if (!$stmt) {
            return response()->json(['error' => 'Prepare failed: ' . $conn->error], 500);
        }

        $stmt->bind_param("s", $socket_id);
        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            return response()->json(['success' => true, 'message' => 'Row deleted successfully']);
        } else {
            $error = $stmt->error;
            $stmt->close();
            $conn->close();
            return response()->json(['error' => 'Delete failed: ' . $error], 500);
        }
    }


    //rename socket
    public function ll_change_socket_name(Request $request)
    {
        $db_name = $request->input('name');
        $socket_id = $request->input('socket_id');
        $socket_name = $request->input('socket_name');
        $conn = new mysqli("localhost", "root", "", $db_name);
        if ($conn->connect_error) {
            return response()->json(['error' => 'Connection failed: ' . $conn->connect_error], 500);
        }
        $stmt = $conn->prepare("UPDATE `light_loads` SET `socket_name` = ? WHERE `socket_id` = ?");
        if (!$stmt) {
            return response()->json(['error' => 'Prepare failed: ' . $conn->error], 500);
        }

        $stmt->bind_param("ss", $socket_name, $socket_id);
        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            return response()->json(['success' => true, 'message' => 'Socket name updated']);
        } else {
            $error = $stmt->error;
            $stmt->close();
            $conn->close();
            return response()->json(['error' => 'Update failed: ' . $error], 500);
        }
    }

    public function ml_change_socket_name(Request $request)
    {
        $db_name = $request->input('name');
        $socket_id = $request->input('socket_id');
        $socket_name = $request->input('socket_name');
        $conn = new mysqli("localhost", "root", "", $db_name);
        if ($conn->connect_error) {
            return response()->json(['error' => 'Connection failed: ' . $conn->connect_error], 500);
        }
        $stmt = $conn->prepare("UPDATE `medium_loads` SET `socket_name` = ? WHERE `socket_id` = ?");
        if (!$stmt) {
            return response()->json(['error' => 'Prepare failed: ' . $conn->error], 500);
        }

        $stmt->bind_param("ss", $socket_name, $socket_id);
        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            return response()->json(['success' => true, 'message' => 'Socket name updated']);
        } else {
            $error = $stmt->error;
            $stmt->close();
            $conn->close();
            return response()->json(['error' => 'Update failed: ' . $error], 500);
        }
    }

    public function hl_change_socket_name(Request $request)
    {
        $db_name = $request->input('name');
        $socket_id = $request->input('socket_id');
        $socket_name = $request->input('socket_name');
        $conn = new mysqli("localhost", "root", "", $db_name);
        if ($conn->connect_error) {
            return response()->json(['error' => 'Connection failed: ' . $conn->connect_error], 500);
        }
        $stmt = $conn->prepare("UPDATE `heavy_loads` SET `socket_name` = ? WHERE `socket_id` = ?");
        if (!$stmt) {
            return response()->json(['error' => 'Prepare failed: ' . $conn->error], 500);
        }

        $stmt->bind_param("ss", $socket_name, $socket_id);
        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            return response()->json(['success' => true, 'message' => 'Socket name updated']);
        } else {
            $error = $stmt->error;
            $stmt->close();
            $conn->close();
            return response()->json(['error' => 'Update failed: ' . $error], 500);
        }
    }

    public function ul_change_socket_name(Request $request)
    {
        $db_name = $request->input('name');
        $socket_id = $request->input('socket_id');
        $socket_name = $request->input('socket_name');
        $conn = new mysqli("localhost", "root", "", $db_name);
        if ($conn->connect_error) {
            return response()->json(['error' => 'Connection failed: ' . $conn->connect_error], 500);
        }
        $stmt = $conn->prepare("UPDATE `universal_loads` SET `socket_name` = ? WHERE `socket_id` = ?");
        if (!$stmt) {
            return response()->json(['error' => 'Prepare failed: ' . $conn->error], 500);
        }

        $stmt->bind_param("ss", $socket_name, $socket_id);
        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            return response()->json(['success' => true, 'message' => 'Socket name updated']);
        } else {
            $error = $stmt->error;
            $stmt->close();
            $conn->close();
            return response()->json(['error' => 'Update failed: ' . $error], 500);
        }
    }


    //adding socket
    public function ll_add_socket(Request $request)
    {
        $db_name = $request->input('name');
        $socket_id = $request->input('socket_id');
        $socket_name = $request->input('socket_name');
        $conn = new mysqli("localhost", "root", "", $db_name);
        if ($conn->connect_error) {
            return response()->json(['error' => 'Connection failed: ' . $conn->connect_error], 500);
        }

        $stmt = $conn->prepare("INSERT INTO `light_loads` (`socket_id`, `socket_name`, `power_status`, `eu_daily`, `ec_daily`, `eu_monthly`, `ec_monthly`) VALUES (?, ?, 0, 0, 0, 0, 0)");
        if (!$stmt) {
            return response()->json(['error' => 'Prepare failed: ' . $conn->error], 500);
        }

        $stmt->bind_param("ss", $socket_id, $socket_name);
        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            return response()->json(['success' => true, 'message' => 'Socket added successfully']);
        } else {
            $error = $stmt->error;
            $stmt->close();
            $conn->close();
            return response()->json(['error' => 'Insert failed: ' . $error], 500);
        }
    }

    public function ml_add_socket(Request $request)
    {
        $db_name = $request->input('name');
        $socket_id = $request->input('socket_id');
        $socket_name = $request->input('socket_name');
        $conn = new mysqli("localhost", "root", "", $db_name);
        if ($conn->connect_error) {
            return response()->json(['error' => 'Connection failed: ' . $conn->connect_error], 500);
        }

        $stmt = $conn->prepare("INSERT INTO `medium_loads` (`socket_id`, `socket_name`, `power_status`, `eu_daily`, `ec_daily`, `eu_monthly`, `ec_monthly`) VALUES (?, ?, 0, 0, 0, 0, 0)");
        if (!$stmt) {
            return response()->json(['error' => 'Prepare failed: ' . $conn->error], 500);
        }

        $stmt->bind_param("ss", $socket_id, $socket_name);
        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            return response()->json(['success' => true, 'message' => 'Socket added successfully']);
        } else {
            $error = $stmt->error;
            $stmt->close();
            $conn->close();
            return response()->json(['error' => 'Insert failed: ' . $error], 500);
        }
    }

    public function hl_add_socket(Request $request)
    {
        $db_name = $request->input('name');
        $socket_id = $request->input('socket_id');
        $socket_name = $request->input('socket_name');
        $conn = new mysqli("localhost", "root", "", $db_name);
        if ($conn->connect_error) {
            return response()->json(['error' => 'Connection failed: ' . $conn->connect_error], 500);
        }

        $stmt = $conn->prepare("INSERT INTO `heavy_loads` (`socket_id`, `socket_name`, `power_status`, `eu_daily`, `ec_daily`, `eu_monthly`, `ec_monthly`) VALUES (?, ?, 0, 0, 0, 0, 0)");
        if (!$stmt) {
            return response()->json(['error' => 'Prepare failed: ' . $conn->error], 500);
        }

        $stmt->bind_param("ss", $socket_id, $socket_name);
        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            return response()->json(['success' => true, 'message' => 'Socket added successfully']);
        } else {
            $error = $stmt->error;
            $stmt->close();
            $conn->close();
            return response()->json(['error' => 'Insert failed: ' . $error], 500);
        }
    }

    public function ul_add_socket(Request $request)
    {
        $db_name = $request->input('name');
        $socket_id = $request->input('socket_id');
        $socket_name = $request->input('socket_name');
        $conn = new mysqli("localhost", "root", "", $db_name);
        if ($conn->connect_error) {
            return response()->json(['error' => 'Connection failed: ' . $conn->connect_error], 500);
        }

        $stmt = $conn->prepare("INSERT INTO `universal_loads` (`socket_id`, `socket_name`, `power_status`, `eu_daily`, `ec_daily`, `eu_monthly`, `ec_monthly`) VALUES (?, ?, 0, 0, 0, 0, 0)");
        if (!$stmt) {
            return response()->json(['error' => 'Prepare failed: ' . $conn->error], 500);
        }

        $stmt->bind_param("ss", $socket_id, $socket_name);
        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            return response()->json(['success' => true, 'message' => 'Socket added successfully']);
        } else {
            $error = $stmt->error;
            $stmt->close();
            $conn->close();
            return response()->json(['error' => 'Insert failed: ' . $error], 500);
        }
    }

    public function change_username(Request $request)
    {
        $oldUn = $request->input('old_un');
        $newUn = $request->input('new_un');

        $conn = new mysqli('localhost', 'root', '');
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $conn->query("CREATE DATABASE `$newUn`");
        $conn->select_db($oldUn);
        $result = $conn->query("SHOW TABLES");

        while ($row = $result->fetch_row()) {
            $table = $row[0];
            $conn->query("CREATE TABLE `$newUn`.`$table` LIKE `$oldUn`.`$table`");
            $conn->query("INSERT INTO `$newUn`.`$table` SELECT * FROM `$oldUn`.`$table`");
        }

        $conn->query("DROP DATABASE `$oldUn`");

        $conn2 = new mysqli('localhost', 'root', '', 'jwt');
        $conn2->query("UPDATE `users` SET `name` = '$newUn' WHERE `name` = '$oldUn'");

        $conn->close();


    }

    // Consumption Update
    public function ll_update_consumption(Request $request) {
        $db_name = $request->input('name');
        $socket_id = $request->input('socket_id');
        $eu_daily = $request->input('eu_daily');
        $ec_daily = $request->input('ec_daily');
        $eu_monthly = $request->input('eu_monthly');
        $ec_monthly = $request->input('ec_monthly');

        $conn = new mysqli("localhost", "root", "", $db_name);
        if ($conn->connect_error) {
            return response()->json(['error' => 'Connection failed: ' . $conn->connect_error], 500);
        }

        $stmt = $conn->prepare("UPDATE `light_loads` SET `eu_daily` = ?, `ec_daily` = ?, `eu_monthly` = ?, `ec_monthly` = ? WHERE `socket_id` = ?");
        $stmt->bind_param("iiiis", $eu_daily, $ec_daily, $eu_monthly, $ec_monthly, $socket_id);
        
        if ($stmt->execute()) {
            $stmt->close(); $conn->close();
            return response()->json(['success' => true]);
        } else {
            $error = $stmt->error; $stmt->close(); $conn->close();
            return response()->json(['error' => $error], 500);
        }
    }

    public function ml_update_consumption(Request $request) {
        $db_name = $request->input('name');
        $socket_id = $request->input('socket_id');
        $eu_daily = $request->input('eu_daily');
        $ec_daily = $request->input('ec_daily');
        $eu_monthly = $request->input('eu_monthly');
        $ec_monthly = $request->input('ec_monthly');

        $conn = new mysqli("localhost", "root", "", $db_name);
        if ($conn->connect_error) {
            return response()->json(['error' => 'Connection failed: ' . $conn->connect_error], 500);
        }

        $stmt = $conn->prepare("UPDATE `medium_loads` SET `eu_daily` = ?, `ec_daily` = ?, `eu_monthly` = ?, `ec_monthly` = ? WHERE `socket_id` = ?");
        $stmt->bind_param("iiiis", $eu_daily, $ec_daily, $eu_monthly, $ec_monthly, $socket_id);
        
        if ($stmt->execute()) {
            $stmt->close(); $conn->close();
            return response()->json(['success' => true]);
        } else {
            $error = $stmt->error; $stmt->close(); $conn->close();
            return response()->json(['error' => $error], 500);
        }
    }

    public function hl_update_consumption(Request $request) {
        $db_name = $request->input('name');
        $socket_id = $request->input('socket_id');
        $eu_daily = $request->input('eu_daily');
        $ec_daily = $request->input('ec_daily');
        $eu_monthly = $request->input('eu_monthly');
        $ec_monthly = $request->input('ec_monthly');

        $conn = new mysqli("localhost", "root", "", $db_name);
        if ($conn->connect_error) {
            return response()->json(['error' => 'Connection failed: ' . $conn->connect_error], 500);
        }

        $stmt = $conn->prepare("UPDATE `heavy_loads` SET `eu_daily` = ?, `ec_daily` = ?, `eu_monthly` = ?, `ec_monthly` = ? WHERE `socket_id` = ?");
        $stmt->bind_param("iiiis", $eu_daily, $ec_daily, $eu_monthly, $ec_monthly, $socket_id);
        
        if ($stmt->execute()) {
            $stmt->close(); $conn->close();
            return response()->json(['success' => true]);
        } else {
            $error = $stmt->error; $stmt->close(); $conn->close();
            return response()->json(['error' => $error], 500);
        }
    }

    public function ul_update_consumption(Request $request) {
        $db_name = $request->input('name');
        $socket_id = $request->input('socket_id');
        $eu_daily = $request->input('eu_daily');
        $ec_daily = $request->input('ec_daily');
        $eu_monthly = $request->input('eu_monthly');
        $ec_monthly = $request->input('ec_monthly');

        $conn = new mysqli("localhost", "root", "", $db_name);
        if ($conn->connect_error) {
            return response()->json(['error' => 'Connection failed: ' . $conn->connect_error], 500);
        }

        $stmt = $conn->prepare("UPDATE `universal_loads` SET `eu_daily` = ?, `ec_daily` = ?, `eu_monthly` = ?, `ec_monthly` = ? WHERE `socket_id` = ?");
        $stmt->bind_param("iiiis", $eu_daily, $ec_daily, $eu_monthly, $ec_monthly, $socket_id);
        
        if ($stmt->execute()) {
            $stmt->close(); $conn->close();
            return response()->json(['success' => true]);
        } else {
            $error = $stmt->error; $stmt->close(); $conn->close();
            return response()->json(['error' => $error], 500);
        }
    }

    // Consumption Reset
    public function ll_reset_consumption(Request $request) {
        $db_name = $request->input('name');
        $socket_id = $request->input('socket_id');
        
        $conn = new mysqli("localhost", "root", "", $db_name);
        if ($conn->connect_error) {
            return response()->json(['error' => 'Connection failed: ' . $conn->connect_error], 500);
        }

        $stmt = $conn->prepare("UPDATE `light_loads` SET `eu_daily` = 0, `ec_daily` = 0, `eu_monthly` = 0, `ec_monthly` = 0 WHERE `socket_id` = ?");
        $stmt->bind_param("s", $socket_id);
        
        if ($stmt->execute()) {
            $stmt->close(); $conn->close();
            return response()->json(['success' => true]);
        } else {
            $error = $stmt->error; $stmt->close(); $conn->close();
            return response()->json(['error' => $error], 500);
        }
    }

    public function ml_reset_consumption(Request $request) {
        $db_name = $request->input('name');
        $socket_id = $request->input('socket_id');
        
        $conn = new mysqli("localhost", "root", "", $db_name);
        if ($conn->connect_error) {
            return response()->json(['error' => 'Connection failed: ' . $conn->connect_error], 500);
        }

        $stmt = $conn->prepare("UPDATE `medium_loads` SET `eu_daily` = 0, `ec_daily` = 0, `eu_monthly` = 0, `ec_monthly` = 0 WHERE `socket_id` = ?");
        $stmt->bind_param("s", $socket_id);
        
        if ($stmt->execute()) {
            $stmt->close(); $conn->close();
            return response()->json(['success' => true]);
        } else {
            $error = $stmt->error; $stmt->close(); $conn->close();
            return response()->json(['error' => $error], 500);
        }
    }

    public function hl_reset_consumption(Request $request) {
        $db_name = $request->input('name');
        $socket_id = $request->input('socket_id');
        
        $conn = new mysqli("localhost", "root", "", $db_name);
        if ($conn->connect_error) {
            return response()->json(['error' => 'Connection failed: ' . $conn->connect_error], 500);
        }

        $stmt = $conn->prepare("UPDATE `heavy_loads` SET `eu_daily` = 0, `ec_daily` = 0, `eu_monthly` = 0, `ec_monthly` = 0 WHERE `socket_id` = ?");
        $stmt->bind_param("s", $socket_id);
        
        if ($stmt->execute()) {
            $stmt->close(); $conn->close();
            return response()->json(['success' => true]);
        } else {
            $error = $stmt->error; $stmt->close(); $conn->close();
            return response()->json(['error' => $error], 500);
        }
    }

    public function ul_reset_consumption(Request $request) {
        $db_name = $request->input('name');
        $socket_id = $request->input('socket_id');
        
        $conn = new mysqli("localhost", "root", "", $db_name);
        if ($conn->connect_error) {
            return response()->json(['error' => 'Connection failed: ' . $conn->connect_error], 500);
        }

        $stmt = $conn->prepare("UPDATE `universal_loads` SET `eu_daily` = 0, `ec_daily` = 0, `eu_monthly` = 0, `ec_monthly` = 0 WHERE `socket_id` = ?");
        $stmt->bind_param("s", $socket_id);
        
        if ($stmt->execute()) {
            $stmt->close(); $conn->close();
            return response()->json(['success' => true]);
        } else {
            $error = $stmt->error; $stmt->close(); $conn->close();
            return response()->json(['error' => $error], 500);
        }
    }

}
