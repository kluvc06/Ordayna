#!/bin/bash
set -euxo pipefail

sudo apt-get update

sudo apt-get install php-cli
sudo apt-get install php-mbstring
sudo apt-get install php-mysql
sudo apt-get install composer
