<?php

declare(strict_types=1);

namespace Error;

function logError(string $msg): bool
{
    if (file_put_contents(
        "error_logs.txt",
        date("Y-m-d H:i:s T", time()) . ": " . $msg . "\n",
        FILE_APPEND | LOCK_EX
    ) === false) {
        return false;
    }
    return true;
}
