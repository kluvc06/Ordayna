<?php

declare(strict_types=1);

namespace Room;

require_once "db.php";

use DB\DB;
use Exception;

class Room
{
    public int $id;
    public string $name;
    public ?string $type;
    public int $space;

    public function __construct(int $id, string $name, ?string $type, int $space)
    {
        $this->id = $id;
        $this->name = $name;
        $this->type = $type;
        $this->space = $space;
    }

    public static function roomExists(DB $db, int $intezmeny_id, int $id): bool|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return ($ret = $db->handleQueryResult($db->connection->execute_query(
                'SELECT EXISTS(SELECT * FROM room WHERE id = ?)',
                array($id)
            ))) === null ? null : $ret[0][0] === 1;
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    public static function roomExistsViaName(DB $db, int $intezmeny_id, string $room_name): bool|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return ($ret = $db->handleQueryResult($db->connection->execute_query(
                'SELECT EXISTS(SELECT * FROM room WHERE name = ?)',
                array($room_name)
            ))) === null ? null : $ret[0][0] === 1;
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    public static function createRoom(DB $db, int $intezmeny_id, string $name, string|null $type, int $space): true|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $db->handleQueryResult($db->connection->execute_query(
                'CALL newRoom(?, ?, ?)',
                array($name, $type, $space)
            ));
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    public static function deleteRoom(DB $db, int $intezmeny_id, int $id): true|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $db->handleQueryResult($db->connection->execute_query(
                'CALL delRoom(?)',
                array($id)
            ));
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    public static function updateRoom(DB $db, int $intezmeny_id, int $id, string $name, string|null $type, int $space): true|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $db->handleQueryResult($db->connection->execute_query(
                'CALL modRoom(?, ?, ?, ?)',
                array($id, $name, $type, $space)
            ));
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    public static function getRooms(DB $db, int $intezmeny_id): array|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            $ret = $db->handleQueryResult($db->connection->query("SELECT id, name, type, space FROM room"));
            if ($ret === null) return null;
            $arr = array();
            for ($i = 0; $i < count($ret); $i++) {
                array_push($arr, new Room((int) $ret[$i][0], $ret[$i][1], $ret[$i][2], (int) $ret[$i][3]));
            }
            return $arr;
        } catch (Exception) {
            return $db->logError(false);
        }
    }
}
