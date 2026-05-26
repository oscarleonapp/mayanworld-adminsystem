<?php

namespace App\Core;

use PDO;
use PDOException;
use Exception;

class Database
{
    private static $instance = null;
    private $connection;
    private $host;
    private $dbname;
    private $username;
    private $password;
    private $charset;

    private function __construct()
    {
        $config = Config::getDbConfig();
        $this->host = $config['host'];
        $this->dbname = $config['dbname'];
        $this->username = $config['username'];
        $this->password = $config['password'];
        $this->charset = $config['charset'];
        
        $this->connect();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function connect()
    {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 10, // Timeout de 10 segundos
                PDO::ATTR_PERSISTENT => false, // No usar conexiones persistentes
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                PDO::MYSQL_ATTR_FOUND_ROWS => true
            ];

            $this->connection = new PDO($dsn, $this->username, $this->password, $options);

            // Asegurar que la conexión use UTF-8
            $this->connection->exec("SET CHARACTER SET utf8mb4");
            $this->connection->exec("SET character_set_connection=utf8mb4");
            $this->connection->exec("SET character_set_client=utf8mb4");
            $this->connection->exec("SET character_set_results=utf8mb4");
        } catch (PDOException $e) {
            if (Config::isDevelopment()) {
                die("Error de conexión: " . $e->getMessage());
            } else {
                die("Error interno del servidor. Intente más tarde.");
            }
        }
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function query($sql, $params = [])
    {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            if (Config::isDevelopment()) {
                throw new Exception("Error en consulta: " . $e->getMessage() . " | SQL: " . $sql);
            } else {
                throw new Exception("Error en la consulta a la base de datos.");
            }
        }
    }

    public function fetch($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    // Alias for fetch() - returns a single row
    public function fetchOne($sql, $params = [])
    {
        return $this->fetch($sql, $params);
    }

    public function fetchAll($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    public function fetchColumn($sql, $params = [], $columnNumber = 0)
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchColumn($columnNumber);
    }

    public function insert($table, $data)
    {
        $fields = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$table} ({$fields}) VALUES ({$placeholders})";
        
        $this->query($sql, $data);
        return $this->connection->lastInsertId();
    }

    public function update($table, $data, $where, $whereParams = [])
    {
        $fields = [];
        foreach (array_keys($data) as $field) {
            $fields[] = "{$field} = :{$field}";
        }
        $fieldsStr = implode(', ', $fields);
        
        $sql = "UPDATE {$table} SET {$fieldsStr} WHERE {$where}";
        
        $params = array_merge($data, $whereParams);
        $stmt = $this->query($sql, $params);
        
        return $stmt->rowCount();
    }

    public function delete($table, $where, $params = [])
    {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    public function count($table, $where = '1=1', $params = [])
    {
        $sql = "SELECT COUNT(*) as total FROM {$table} WHERE {$where}";
        $result = $this->fetch($sql, $params);
        return (int) $result['total'];
    }

    public function beginTransaction()
    {
        return $this->connection->beginTransaction();
    }

    public function commit()
    {
        return $this->connection->commit();
    }

    public function rollback()
    {
        return $this->connection->rollback();
    }

    public function lastInsertId()
    {
        return $this->connection->lastInsertId();
    }

    public function columnExists($table, $column)
    {
        $sql = "SELECT COUNT(*) as total FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table AND COLUMN_NAME = :column";
        $result = $this->fetch($sql, [
            'schema' => $this->dbname,
            'table' => $table,
            'column' => $column
        ]);

        return isset($result['total']) ? (int)$result['total'] > 0 : false;
    }

    public function tableExists($table)
    {
        $sql = "SELECT COUNT(*) as total FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table";
        $result = $this->fetch($sql, [
            'schema' => $this->dbname,
            'table' => $table
        ]);

        return isset($result['total']) ? (int)$result['total'] > 0 : false;
    }

    private function __clone() {}
    
    public function __wakeup()
    {
        throw new Exception("No se puede deserializar el singleton Database");
    }
}
