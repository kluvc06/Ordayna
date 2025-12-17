#!/bin/bash
set -euxo pipefail

sudo apt-get update

sudo apt-get install php-cli -y
sudo apt-get install php-mbstring -y
sudo apt-get install php-mysql -y
sudo apt-get install composer -y

composer require lcobucci/jwt
composer require lcobucci/clock
