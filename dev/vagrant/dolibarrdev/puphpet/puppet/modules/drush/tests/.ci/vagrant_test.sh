#!/bin/bash

cd /vagrant
chmod a+x tests/.ci/test.sh
./tests/.ci/test.sh
