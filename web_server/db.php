<?php

declare(strict_types=1);

namespace DB;

require_once "error.php";
require_once "config.php";
require_once "intezmeny_sql.php";

use Config\Config;
use function Error\logError;
use Exception;
use mysqli;
use mysqli_result;

class DB
{
    public mysqli $connection;

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    public static function init(): DB|null
    {
        try {
            $connection = mysqli_connect(Config::$database_address, Config::$database_username, Config::$database_password, Config::$database_name);
            if ($connection === false) {
                logError(mysqli_connect_error());
                return null;
            }
            return new DB($connection);
        } catch (Exception) {
            logError(mysqli_connect_error());
            return null;
        }
    }

    /** Returns first results contents as 2d array or null if $cur_result is null */
    private function freeRemainingResults(mysqli_result|null $cur_result): array|null
    {
        $arr = null;
        if ($cur_result !== null) {
            $arr = $cur_result->fetch_all();
            $cur_result->free_result();
        }
        while ($this->connection->next_result() !== false) {
            if ($this->connection->errno !== 0) return null;
            $next = $this->connection->store_result();
            if ($next !== false) $next->free_result();
        }
        return $arr;
    }

    /**
     * Return $ret_value
     * Only logs error if $ret_value === false
     */
    public function logError(mysqli_result|bool $ret_value): mysqli_result|bool|null
    {
        if ($ret_value === false) {
            logError($this->connection->error);
            return null;
        }
        return $ret_value;
    }

    /** Frees all results but only returns the first */
    public function handleQueryResult(mysqli_result|bool $ret): array|bool|null
    {
        if ($ret === false) {
            $this->freeRemainingResults(null);
            return $this->logError(false);
        }
        if ($ret === true) {
            $this->freeRemainingResults(null);
            return true;
        }
        return $this->freeRemainingResults($ret);
    }
}
