# #!/bin/bash
# set -euo pipefail

# sudo apt-get update

# sudo apt-get install mariadb-server -y
# sudo apt-get install php-mysql -y
# # 7zip or unzip is required for composer to work but neither is a dependancy for some reason
# sudo apt-get install 7zip -y
# sudo apt-get install composer -y

# orig_dir=$(pwd)
# cd ../backend
# composer require lcobucci/jwt
# composer require lcobucci/clock
# cd "$orig_dir"

# # Generate the random secret used for jwt hashing
# openssl rand -out ../backend/secret.key -base64 128

# sudo mariadb -u root -e "source ../db/main_db.sql"
# sudo mariadb -u root -e "use ordayna_main_db;source ../db/main_db_procedures.sql;source ../db/test_data.sql;"

# printf "Make sure that the /etc/mysql/mariadb.cnf file includes the following lines:\n[mysqld]\nevent_scheduler = on\n"
# printf "If you have updated this file than run the following command: sudo systemctl restart mariadb\n"

cd ..
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('sha384', 'composer-setup.php') === 'c8b085408188070d5f52bcfe4ecfbee5f727afa458b2573b8eaaf77b3419b0bf2768dc67c86944da1544f06fa544fd47') { echo 'Installer verified'.PHP_EOL; } else { echo 'Installer corrupt'.PHP_EOL; unlink('composer-setup.php'); exit(1); }"
php composer-setup.php
php -r "unlink('composer-setup.php');"

cd backend
php ../composer.phar require lcobucci/jwt
php ../composer.phar require lcobucci/clock

c:\xampp\mysql\bin\mysql.exe -u root -e "source ../db/main_db.sql"
c:\xampp\mysql\bin\mysql.exe -u root -e "use ordayna_main_db;source ../db/main_db_procedures.sql;source ../db/test_data.sql;"

echo "very secret key because windows sucks" > secret.key

cd ../config
