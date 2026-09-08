<?php
namespace App\Core;

use PDO;
use PDOException;

/**
 * Singleton PDO wrapper. Raw PDO + prepared statements only, no ORM.
 */
class Database
{
    private static ?PDO $instance = null;

    public static function connect(): PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $host = Env::get('DB_HOST', '127.0.0.1');
        $port = Env::get('DB_PORT', '3306');
        $name = Env::get('DB_NAME', 'sales_inventory');
        $user = Env::get('DB_USER', 'root');
        $pass = Env::get('DB_PASS', '');
        $charset = 'utf8mb4';

        $dsn = "mysql:host=$host;port=$port;dbname=$name;charset=$charset";

        try {
            self::$instance = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Database connection failed. Check your .env settings.',
                'error'   => Env::get('APP_DEBUG', 'false') === 'true' ? $e->getMessage() : null,
            ]);
            exit;
        }

        return self::$instance;
    }
}
