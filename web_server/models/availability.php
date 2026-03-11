<?php

declare(strict_types=1);

namespace Availability;

require_once "db.php";

use DateTime;

class Availability
{
    public int $id;
    public int $available_from_day;
    public DateTime $available_from_time;
    public int $available_until_day;
    public DateTime $available_until_time;

    public function __construct(int $id, int $available_from_day, DateTime $available_from_time, int $available_until_day, DateTime $available_until_time)
    {
        $this->id = $id;
        $this->available_from_day = $available_from_day;
        $this->available_from_time = $available_from_time;
        $this->available_until_day = $available_until_day;
        $this->available_until_time = $available_until_time;
    }
}
