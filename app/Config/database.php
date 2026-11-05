<?php
/**
 * Digital Crazy Hub - Database Configuration & Manager
 */

declare(strict_types=1);

namespace App\Config;

use PDO;
use PDOException;
use Throwable;

class Database {
    private static ?PDO $pdo = null;
    private static bool $connectionAttempted = false;
    private static string $fallbackFile = STORAGE_DIR . '/queue/enrollments.json';
    private static string $syncQueueFile = STORAGE_DIR . '/queue/sync_queue.json';

    public static function getConfig(): array {
        return [
            'host'     => (string)env('DB_HOST', 'localhost'),
            'port'     => (int)env('DB_PORT', 3306),
            'database' => (string)env('DB_NAME', 'dch_workshop'),
            'username' => (string)env('DB_USER', 'root'),
            'password' => (string)env('DB_PASS', ''),
            'charset'  => 'utf8mb4'
        ];
    }

    public static function getConnection(): ?PDO {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        if (self::$connectionAttempted) {
            return null;
        }

        self::$connectionAttempted = true;
        $cfg = self::getConfig();

        $dsn = "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['database']};charset={$cfg['charset']}";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 3
        ];

        try {
            self::$pdo = new PDO($dsn, $cfg['username'], $cfg['password'], $options);
            return self::$pdo;
        } catch (PDOException $e) {
            error_log("Database: MySQL unavailable (" . $e->getMessage() . "). Falling back to resilient storage.");
            self::$pdo = null;
            return null;
        }
    }

    public static function getFallbackPath(): string {
        return self::$fallbackFile;
    }

    public static function getSyncQueuePath(): string {
        return self::$syncQueueFile;
    }
}

