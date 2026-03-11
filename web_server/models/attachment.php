<?php

declare(strict_types=1);

namespace Attachment;

require_once "db.php";

use DB\DB;
use Exception;

class Attachment
{
    public int $id;
    public string $file_name;

    public function __construct(int $id, string $file_name)
    {
        $this->id = $id;
        $this->file_name = $file_name;
    }

    public static function attachmentExists(DB $db, int $intezmeny_id, int $attachment_id): bool|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return ($ret = $db->handleQueryResult($db->connection->execute_query(
                'SELECT EXISTS(SELECT * FROM attachments WHERE id = ?)',
                array($attachment_id)
            ))) === null ? null : $ret[0][0] === 1;
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    /**
     * Returns the new attachments id
     */
    public static function createAttachment(DB $db, int $intezmeny_id, int $homework_id, string $file_name): int|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            if ($db->handleQueryResult($db->connection->execute_query(
                'CALL newAttachment(?, ?)',
                array($homework_id, $file_name)
            )) === null) return null;
            // Have to make an sql query here since calling a procedure overwrites mysqli_insert_id
            return ($ret = $db->handleQueryResult(
                $db->connection->query("SELECT LAST_INSERT_ID()")
            )) === null ? null : (int) $ret[0][0];
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    public static function deleteAttachment(DB $db, int $intezmeny_id, int $attachment_id): true|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return $db->handleQueryResult($db->connection->execute_query(
                'CALL delAttachment(?)',
                array($attachment_id)
            ));
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    public static function getHomeworkAttachments(DB $db, int $intezmeny_id, int $homework_id): array|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return ($ret = $db->handleQueryResult($db->connection->execute_query(
                'SELECT attachments.id, file_name FROM homework LEFT JOIN attachments on homework.id = homework_id WHERE homework.id = ?',
                array($homework_id)
            ))) === null ? null : $ret;
        } catch (Exception) {
            return $db->logError(false);
        }
    }

    public static function getAttachmentName(DB $db, int $intezmeny_id, int $attachment_id): string|null
    {
        try {
            if ($db->logError($db->connection->select_db('ordayna_intezmeny_' . $intezmeny_id)) === null) return null;
            return ($ret = $db->handleQueryResult($db->connection->execute_query(
                "SELECT file_name FROM attachments WHERE id = ?",
                array($attachment_id)
            ))) === null ? null : $ret[0][0];
        } catch (Exception) {
            return $db->logError(false);
        }
    }
}
