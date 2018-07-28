#!/bin/bash

if [ ! -f "/usr/local/bin/composer" ]; then
    echo "Installing Composer"
    php -r "readfile('https://getcomposer.org/installer');" | sudo php -d apc.enable_cli=0 -- --install-dir=/usr/local/bin --filename=composer
else
    echo "Updating Composer"
    sudo /usr/local/bin/composer self-update
fi

echo "Installing dependencies"
composer install

echo "Installing supervisord"
sudo apt-get install supervisor -y --no-install-recommends
sudo cp ./.ci/phpunit-environment.conf /etc/supervisor/conf.d/
sudo sed -i "s/^directory=.*webserver$/directory=${ESCAPED_BUILD_DIR}\\/selenium-1-tests/" /etc/supervisor/conf.d/phpunit-environment.conf
sudo sed -i "s/^autostart=.*selenium$/autostart=true/" /etc/supervisor/conf.d/phpunit-environment.conf
sudo sed -i "s/^autostart=.*python-webserver$/autostart=true/" /etc/supervisor/conf.d/phpunit-environment.conf

echo "Installing Firefox"
sudo apt-get install firefox -y --no-install-recommends

echo "Installing Java 8"
sudo apt-add-repository ppa:openjdk-r/ppa -y
sudo apt-get update
sudo apt-get install openjdk-8-jre-headless -y --no-install-recommends
sudo update-alternatives --set java /usr/lib/jvm/java-8-openjdk-amd64/jre/bin/java

if [ ! -f "$SELENIUM_JAR" ]; then
    echo "Downloading Selenium"
    sudo mkdir -p $(dirname "$SELENIUM_JAR")
    sudo wget -nv -O "$SELENIUM_JAR" "$SELENIUM_DOWNLOAD_URL"
fi

if [ ! -f "/usr/local/bin/geckodriver" ]; then
    echo "Downloading geckodriver"
    sudo wget -nv -O "$GECKODRIVER_TAR" "$GECKODRIVER_DOWNLOAD_URL"
    sudo tar -xvf "$GECKODRIVER_TAR" -C "/usr/local/bin/"
fi
