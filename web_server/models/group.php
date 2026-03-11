<?php

declare(strict_types=1);

namespace Group;

require_once "db.php";
require_once "models/class.php";

use Class_\Class_;
use DB\DB;
use Exception;

class Group
{
    public int $id;
    public string $name;
    public int $headcount;
    public ?Class_ $class;

    public function __construct(int $id, string $name, int $headcount, ?Class_ $class)
    {
        $this->id = $id;
        $this->name = $name;
        $this->headcount = $headcount;
        $this->class = $class;
    }

    public static function groupExists(DB $db, int $intezmeny_id, int $id): bool|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return ($ret = $db->handleQueryResult($db->connection->execute_query(
                'SELECT EXISTS(SELECT * FROM group_ WHERE id = ?)',
                array($id)
            ))) === null ? null : $ret[0][0] === 1;
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    public static function groupExistsViaName(DB $db, int $intezmeny_id, string $group_name): bool|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return ($ret = $db->handleQueryResult($db->connection->execute_query(
                'SELECT EXISTS(SELECT * FROM group_ WHERE name = ?)',
                array($group_name)
            ))) === null ? null : $ret[0][0] === 1;
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    public static function createGroup(DB $db, int $intezmeny_id, string $name, int $headcount, int|null $class_id): true|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $db->handleQueryResult($db->connection->execute_query(
                'CALL newGroup(?, ?, ?)',
                array($name, $headcount, $class_id)
            ));
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    public static function deleteGroup(DB $db, int $intezmeny_id, int $id): true|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $db->handleQueryResult($db->connection->execute_query(
                'CALL delGroup(?)',
                array($id)
            ));
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    public static function updateGroup(DB $db, int $intezmeny_id, int $id, string $name, int $headcount, int|null $class_id): true|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $db->handleQueryResult($db->connection->execute_query(
                'CALL modGroup(?, ?, ?, ?)',
                array($id, $name, $headcount, $class_id)
            ));
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    public static function getGroups(DB $db, int $intezmeny_id): array|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            $ret = $db->handleQueryResult($db->connection->query(
                '
                    SELECT group_.id, group_.name, group_.headcount, class.id, class.name
                    FROM group_ LEFT JOIN class ON group_.class_id = class.id
                '
            ));
            if ($ret === null) return null;
            $arr = array();
            for ($i = 0; $i < count($ret); $i++) {
                array_push($arr, new Group(
                    (int) $ret[$i][0],
                    $ret[$i][1],
                    (int) $ret[$i][2],
                    $ret[$i][3] !== null ? new Class_((int) $ret[$i][3], $ret[$i][4]) : null
                ));
            }
            return $arr;
        } catch (Exception) {
            return $db->logError(false);
        }
    }
}
