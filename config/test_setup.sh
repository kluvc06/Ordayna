#!/bin/bash
set -euo pipefail

sudo mariadb -u root -e "source ../db/main_db.sql;use ordayna_main_db;source ../db/main_db_procedures.sql;source ../db/test_data.sql;"

# 7zip or unzip or git is required for composer to work but non of them are a dependancy for some reason
sudo apt-get install --update mariadb-server php-mysql 7zip composer -y

orig_dir=$(pwd)
cd ../web_server
composer require lcobucci/jwt lcobucci/clock
printf "localhost" > "database_address"
cd "$orig_dir"

# Generate the random secret used for jwt hashing
openssl rand -out ../web_server/secret.key -base64 128

printf "Make sure that the /etc/mysql/mariadb.cnf file includes the following lines:\n[mysqld]\nevent_scheduler = on\n"
printf "If you have updated this file than run the following command: sudo systemctl restart mariadb\n"
