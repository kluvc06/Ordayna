#!/bin/bash
set -euo pipefail

sudo apt-get --update full-upgrade -y
sudo apt-get install mariadb-server nginx php-fpm php-mysql 7zip composer -y

db_password=$(openssl rand -base64 48)

sudo mariadb -u root -e "source ../db/main_db.sql;use ordayna_main_db;source ../db/main_db_procedures.sql;"
sudo mariadb -u root -e "CREATE OR REPLACE USER ordayna_main@localhost IDENTIFIED BY \"$db_password\";GRANT ALL ON *.* TO ordayna_main@localhost;"

rm -f ../dhparam
curl https://ssl-config.mozilla.org/ffdhe2048.txt > ../dhparam

sudo rm -rf /etc/nginx/
sudo mkdir /etc/nginx
sudo cp -r ./nginx_config/* /etc/nginx/
# Generate the random secret used for jwt hashing
jwt_secret=$(openssl rand -base64 32)
# Generate the certificate and secret used for https
rm -f ../ordayna.pem
rm -f ../ordayna.key
openssl req -x509 -nodes -days 730 -newkey rsa:2048 -keyout ../ordayna.key -out ../ordayna.pem -config san.cnf

sudo systemctl restart nginx php8.4-fpm

cd ../web_server
composer require lcobucci/jwt lcobucci/clock ramsey/uuid
rm -f config.php
printf "<?php\n\n" | tee -a config.php > /dev/null
printf "declare(strict_types=1);\n\n" | tee -a config.php > /dev/null
printf "namespace Config;\n\n" | tee -a config.php > /dev/null
printf "class Config\n" | tee -a config.php > /dev/null
printf "{\n" | tee -a config.php > /dev/null
printf "    public static ?string \$database_address = \"localhost\";\n" | tee -a config.php > /dev/null
printf "    public static ?string \$database_username = \"ordayna_main\";\n" | tee -a config.php > /dev/null
printf "    public static ?string \$database_password = \"%s\";\n" "$db_password" | tee -a config.php > /dev/null
printf "    public static ?string \$database_name = null;\n\n" | tee -a config.php > /dev/null
printf "    public static string \$jwt_secret = \"%s\";\n" "$jwt_secret" | tee -a config.php > /dev/null
printf "}\n" | tee -a config.php > /dev/null
cd ../config

# This is intentionally not recursive
sudo rm -f ../web_server/error_logs.txt
touch ../web_server/error_logs.txt
sudo chown -R www-data:www-data ../web_server/error_logs.txt

sudo rm -rf ../web_server/user_data
mkdir ../web_server/user_data
sudo chown -R www-data:www-data ../web_server/user_data

printf "Make sure that the /etc/mysql/mariadb.cnf file includes the following lines:\n[mysqld]\nevent_scheduler = on\n"
printf "If you have updated this file than run the following command: sudo systemctl restart mariadb\n"
