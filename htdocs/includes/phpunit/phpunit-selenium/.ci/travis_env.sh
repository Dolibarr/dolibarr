#!/bin/bash

ESCAPED_BUILD_DIR=$(echo "$TRAVIS_BUILD_DIR" | sed 's/\//\\\//g')
