<?php

declare(strict_types=1);

namespace Class_;

require_once "db.php";

use DB\DB;
use Exception;

class Class_
{
    public int $id;
    public string $name;

    public function __construct(int $id, string $name)
    {
        $this->id = $id;
        $this->name = $name;
    }

    public static function classExists(DB $db, int $intezmeny_id, int $id): bool|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return ($ret = $db->handleQueryResult($db->connection->execute_query(
                'SELECT EXISTS(SELECT * FROM class WHERE id = ?)',
                array($id)
            ))) === null ? null : $ret[0][0] === 1;
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    public static function classExistsViaName(DB $db, int $intezmeny_id, string $name): bool|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return ($ret = $db->handleQueryResult($db->connection->execute_query(
                'SELECT EXISTS(SELECT * FROM class WHERE name = ?)',
                array($name)
            ))) === null ? null : $ret[0][0] === 1;
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    public static function createClass(DB $db, int $intezmeny_id, string $name, int $headcount): true|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $db->handleQueryResult($db->connection->execute_query(
                'CALL newClass(?, ?)',
                array($name, $headcount)
            ));
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    public static function deleteClass(DB $db, int $intezmeny_id, int $id): true|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $db->handleQueryResult($db->connection->execute_query(
                'CALL delClass(?)',
                array($id)
            ));
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    public static function updateClass(DB $db, int $intezmeny_id, int $id, string $name): true|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $db->handleQueryResult($db->connection->execute_query(
                'CALL modClass(?, ?)',
                array($id, $name)
            ));
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    public static function getClasses(DB $db, int $intezmeny_id): array|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            $ret = $db->handleQueryResult($db->connection->query("SELECT id, name FROM class"));
            if ($ret === null) return null;
            $arr = array();
            for ($i = 0; $i < count($ret); $i++) {
                array_push($arr, new Class_((int) $ret[$i][0], $ret[$i][1]));
            }
            return $arr;
        } catch (Exception) {
            return $db->logError(false);
        }
    }
}
