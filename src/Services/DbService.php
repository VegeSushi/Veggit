<?php

namespace Vegesushi\Veggit\Services;

use Delight\Auth\Auth;
use PDO;
use Dotenv\Dotenv;

class DbService
{
    private PDO $db;
    private Auth $auth;

    public function __construct(string $projectRoot)
    {
        $dotenv = Dotenv::createImmutable($projectRoot);
        $dotenv->load();

        if (!isset($_ENV['DB_PATH']) || $_ENV['DB_PATH'] === '') {
            throw new \RuntimeException("Environment variable DB_PATH is not set");
        }

        $dbPath = $_ENV['DB_PATH'];

        $dir = dirname($dbPath);
        if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
            throw new \RuntimeException("Failed to create directory for SQLite database: {$dir}");
        }

        $dsn = "sqlite:" . $dbPath;

        try {
            $this->db = new PDO($dsn);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            throw new \RuntimeException('Database connection failed: ' . $e->getMessage());
        }

        $this->auth = new Auth($this->db);
    }

    public function getAuth(): Auth
    {
        return $this->auth;
    }

    public function getDb(): PDO
    {
        return $this->db;
    }
}
