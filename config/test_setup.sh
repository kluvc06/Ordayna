#!/bin/bash
set -euo pipefail

sudo apt-get update

sudo apt-get install mariadb-server -y
sudo apt-get install php-mysql -y
# 7zip or unzip is required for composer to work but neither is a dependancy for some reason
sudo apt-get install 7zip -y
sudo apt-get install composer -y

orig_dir=$(pwd)
cd ../backend
composer require lcobucci/jwt
composer require lcobucci/clock
cd "$orig_dir"

# Generate the random secret used for jwt hashing
openssl rand -out ../backend/secret.key -base64 128

sudo mariadb -u root -e "source ../db/main_db.sql"

printf "Make sure that the /etc/mysql/mariadb.cnf file includes the following lines:\n[mysqld]\nevent_scheduler = on\n"
printf "If you have updated this file than run the following command: sudo systemctl restart mariadb\n"
