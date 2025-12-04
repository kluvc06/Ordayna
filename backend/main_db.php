
<?php

class MainDb
{
    private $connection = null;

    public function __construct()
    {
        $databaseHost = 'localhost';
        $databaseUsername = 'ordayna_main';
        $databasePassword = '';
        $databaseName = 'ordayna_main_db';

        $this->connection = mysqli_connect($databaseHost, $databaseUsername, $databasePassword, $databaseName);
    }

    function getAllUsers(int $intezmeny_id)
    {
        return $this->connection->execute_query(
            '
            SELECT display_name, email, phone_number FROM intezmeny_ids
            LEFT JOIN intezmeny_ids_users ON intezmeny_ids_id = intezmeny_ids.id
            LEFT JOIN users ON users_id = users.id

            WHERE intezmeny_ids.intezmeny_id = ?
            ;
        ',
            array($intezmeny_id),
        );
    }
}
