#!/bin/bash

sudo killall supervisord
sudo killall -9 java
sudo killall -9 Xvfb
sudo rm -f /tmp/.X99-lock
sudo /etc/init.d/supervisor start

wget --retry-connrefused --tries=120 --waitretry=3 --output-file=/dev/null "$SELENIUM_HUB_URL/wd/hub/status" -O /dev/null
if [ ! $? -eq 0 ]; then
    echo "Selenium Server not started"
else
    echo "Finished setup"
fi
