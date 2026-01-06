
#!/bin/bash
set -euo pipefail

sudo apt-get update
sudo apt-get full-upgrade -y

sudo apt-get install mariadb-server -y
sudo apt-get install nginx -y
sudo apt-get install php-fpm -y
sudo apt-get install php-mysql -y
# 7zip or unzip is required for composer to work but neither is a dependancy for some reason
sudo apt-get install 7zip -y
sudo apt-get install composer -y

sudo rm -f /etc/nginx/sites-available/filer
sudo cp ./filer /etc/nginx/sites-available/
sudo rm -f /etc/nginx/sites-enabled/filer
sudo ln -s /etc/nginx/sites-available/filer /etc/nginx/sites-enabled/filer
sudo rm -rf /var/www/filer
sudo mkdir /var/www/filer
sudo cp ../backend/* /var/www/filer/ -r

orig_dir=$(pwd)
cd /var/www/filer
sudo composer require lcobucci/jwt lcobucci/clock
cd "$orig_dir"

# Generate the random secret used for jwt hashing
sudo openssl rand -out /var/www/filer/secret.key -base64 128
# Generate the certificate and secret used for https
openssl req -x509 -nodes -days 730 -newkey rsa:2048 -keyout filer.key -out filer.pem -config san.cnf
sudo rm -f /etc/ssl/certs/filer.pem
sudo mv filer.pem /etc/ssl/certs/
sudo rm -f /etc/ssl/private/filer.key
sudo mv filer.key /etc/ssl/private/

# This is intentionally not recursive
sudo chmod a+wr /var/www/filer/

sudo mariadb -u root -e "source ../db/main_db.sql"

sudo systemctl restart nginx php8.4-fpm

printf "Make sure that the /etc/mysql/mariadb.cnf file includes the following lines:\n[mysqld]\nevent_scheduler = on\n"
printf "If you have updated this file than run the following command: sudo systemctl restart mariadb\n"
