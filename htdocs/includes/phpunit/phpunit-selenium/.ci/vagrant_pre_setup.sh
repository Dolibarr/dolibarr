#!/bin/sh

sed -i "/mirror:\\/\\//d" /etc/apt/sources.list
sed -i "1ideb mirror://mirrors.ubuntu.com/mirrors.txt precise main restricted universe multiverse" /etc/apt/sources.list
sed -i "1ideb mirror://mirrors.ubuntu.com/mirrors.txt precise-updates main restricted universe multiverse" /etc/apt/sources.list
sed -i "1ideb mirror://mirrors.ubuntu.com/mirrors.txt precise-backports main restricted universe multiverse" /etc/apt/sources.list
sed -i "1ideb mirror://mirrors.ubuntu.com/mirrors.txt precise-security main restricted universe multiverse" /etc/apt/sources.list

apt-get update

apt-get install software-properties-common -y
apt-get install python-software-properties -y

apt-add-repository ppa:ondrej/php -y

apt-get update

# installing xvfb, java and php
apt-get install xvfb php5.6-cli php5.6-curl php5.6-xml php-xdebug ncurses-term unzip xfonts-100dpi xfonts-75dpi xfonts-scalable xfonts-cyrillic vim -y --no-install-recommends

