#!/bin/bash
set -euxo pipefail

sudo apt-get update

sudo apt-get install php-mysql -y
sudo apt-get install composer -y

composer require lcobucci/jwt
composer require lcobucci/clock

rm secret.key || true
echo "tester keylengthlengthlengthlengthlengthlengthlengthlengthlengthlengthlengthlengthlength" > secret.key
