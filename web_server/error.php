<?php

declare(strict_types=1);

function logError(string $msg): bool
{
    if (file_put_contents(
        "error_logs.txt",
        date("Y-m-d H:i:s T", time()) . ": " . $msg . "\n",
        FILE_APPEND | LOCK_EX
    ) === false) {
        fwrite(STDOUT, "Failed to write logs to \"error_logs.txt\" file\n");
        return false;
    }
    return true;
}
