<?php

include "main_db.php";

class UserController
{
    /**
    * Returns null on failure and users in json format on success
    */
    public function getAllUsers(): mixed {
        if (!isset($_GET["intezmeny_id"]) or intval($_GET["intezmeny_id"]) === 0) {
            return null;
        }

        $intezmeny_id = intval($_GET["intezmeny_id"]);

        $main_db = new MainDb();
        $res = $main_db->getAllUsers($intezmeny_id);

        return json_encode($res->fetch_all());
    }
}
