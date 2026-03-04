#!/bin/bash
set -euo pipefail

sudo apt-get --update install docker\* -y

cd ../web_server
sudo docker build . -t ordayna-backend
rm -f config.php
printf "<?php\n\ndeclare(strict_types=1);\n\nclass Config {\n" | tee -a config.php > /dev/null
printf "    public static ?string \$database_address = \"database:3306\";\n" | tee -a config.php > /dev/null
printf "    public static ?string \$database_username = \"ordayna_main\";\n" | tee -a config.php > /dev/null
printf "    public static ?string \$database_password = \"very secret\";\n" | tee -a config.php > /dev/null
printf "    public static ?string \$database_name = null;\n\n" | tee -a config.php > /dev/null
printf "    public static string \$jwt_secret = \"very secretvery secretvery secret\";\n" | tee -a config.php > /dev/null
printf "}\n" | tee -a config.php > /dev/null
cd ../config

printf "Run by running run_on_linux.sh in the project root folder"

