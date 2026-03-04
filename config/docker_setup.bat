@echo off
echo Make sure that docker desktop is installed

cd ../web_server
docker build . -t ordayna-backend
del config.php
echo ^<?php >> config.php
echo: >> config.php
echo declare(strict_types=1); >> config.php
echo: >> config.php
echo class Config { >> config.php
echo     public static ?string $database_address = "database:3306"; >> config.php
echo     public static ?string $database_username = "ordayna_main"; >> config.php
echo     public static ?string $database_password = "very secret"; >> config.php
echo     public static ?string $database_name = null; >> config.php
echo: >> config.php
echo     public static string $jwt_secret = "very secretvery secretvery secret"; >> config.php
echo } >> config.php
cd ../config

echo Run by double clicking on run_on_windows.bat in the project root folder

