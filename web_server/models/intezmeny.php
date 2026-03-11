<?php

declare(strict_types=1);

namespace Intezmeny;

use DB\DB;
use Exception;

require_once "db.php";

class Intezmeny
{
    public int $id;
    public string $name;

    public function __construct(int $id, string $name)
    {
        $this->id = $id;
        $this->name = $name;
    }

    public static function getIntezmenys(DB $db, int $uid): array|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_main_db')) === null) return null;
            $ret = $db->handleQueryResult($db->connection->execute_query(
                '
                    SELECT intezmeny.id, intezmeny.name FROM users
                    INNER JOIN intezmeny_users ON intezmeny_users.users_id = users.id
                    INNER JOIN intezmeny ON intezmeny_users.intezmeny_id = intezmeny.id
                    WHERE users.id = ?
                ',
                array($uid)
            ));
            if ($ret === null) return null;
            $arr = array();
            for ($i = 0; $i < count($ret); $i++) {
                array_push($arr, new Intezmeny((int) $ret[$i][0], $ret[$i][1]));
            }
            return $arr;
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    public static function createIntezmeny(DB $db, string $intezmeny_name, int $admin_uid): true|null
    {
        global $intezmeny_tables, $intezmeny_procedures;
        
        try {
            if ($db->logError($db->connection->select_db('ordayna_main_db')) === null) return null;
            if ($db->handleQueryResult($db->connection->execute_query(
                'INSERT INTO intezmeny (name) VALUE (?)',
                array($intezmeny_name)
            )) === null) return null;
            $intezmeny_id = $db->connection->insert_id;
            if ($db->handleQueryResult($db->connection->execute_query(
                'INSERT INTO intezmeny_users (intezmeny_id, users_id, role_, invite_accepted) VALUE (?, ?, "admin", TRUE)',
                array($intezmeny_id, $admin_uid)
            )) === null) return null;
            $db->connection->multi_query(
                '
                    -- db allows us to replace the database without encountering foreign key errors
                    SET FOREIGN_KEY_CHECKS = 0;
                    CREATE OR REPLACE DATABASE ordayna_intezmeny_' . $intezmeny_id . ' CHARACTER SET = "utf8mb4" COLLATE = "utf8mb4_uca1400_ai_ci";
                    SET FOREIGN_KEY_CHECKS = 1;

                    USE ordayna_intezmeny_' . $intezmeny_id . ';
                ' . $intezmeny_tables . $intezmeny_procedures,
            );
            if ($db->connection->errno !== 0) return null;
            while ($db->connection->next_result() !== false) {
                if ($db->connection->errno !== 0) return null;
            }
            return true;
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    public static function deleteIntezmeny(DB $db, int $intezmeny_id): bool|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_main_db')) === null) return null;
            if ($db->handleQueryResult($db->connection->execute_query(
                'DELETE FROM intezmeny WHERE id = ?',
                array($intezmeny_id)
            )) === null) return null;
            return $db->handleQueryResult($db->connection->query("DROP DATABASE ordayna_intezmeny_" . $intezmeny_id));
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    /** The second worst thing to happen to those orphans */
    public static function deleteOrphanedIntezmenys(DB $db): true|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_main_db')) === null) return null;
            $ids = $db->handleQueryResult($db->connection->query("CALL getOrphanedIntezmenys()"));
            if ($ids === null) return null;
            for ($i = 0; $i < count($ids); $i++) {
                if ($db->handleQueryResult($db->connection->execute_query(
                    'DELETE FROM intezmeny WHERE id = ?',
                    array($ids[$i][0])
                )) === null) return null;
                if ($db->handleQueryResult($db->connection->query('DROP DATABASE ordayna_intezmeny_' . $ids[$i][0])) === null) return null;
            }
            return true;
        } catch (Exception) {
            return $db->logError(false);
        }
    }
}
