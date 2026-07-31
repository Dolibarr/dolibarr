#!/bin/bash
# Copyright (C) 2026		MDW	<mdeweerd@users.noreply.github.com>

# Wrapper script to run hurl tests
# This script starts a PHP server, runs the tests, and tears the PHP server down

set -e

# Configuration
PHPSERVER_LOG="/tmp/phpserver_shipping.log"
PHPSERVER_PORT="9999"
PHPSERVER_HOST="127.0.50.1"
COOKIEJAR="/tmp/hurl_cookie_shipping_$$.jar"
DOLIUSERNAME="admin"
DOLIPASSWORD="admin"

# Cleanup function to ensure PHP server is killed on any exit
cleanup() {
    echo ""
    echo "Cleaning up..."
    kill $PHPSERVER_PID 2>/dev/null || true
    wait $PHPSERVER_PID 2>/dev/null || true
    rm -f "$COOKIEJAR"
    echo "PHP server stopped"
}

# Set trap to cleanup on EXIT, INT, TERM
trap cleanup EXIT INT TERM

# Kill any existing PHP servers on this port
pkill -f "php -S $PHPSERVER_HOST:$PHPSERVER_PORT" 2>/dev/null || true
sleep 1

# Start PHP server
echo "Starting PHP server on $PHPSERVER_HOST:$PHPSERVER_PORT..."
php -S $PHPSERVER_HOST:$PHPSERVER_PORT -t htdocs > $PHPSERVER_LOG 2>&1 &
PHPSERVER_PID=$!

# Give the server time to start
sleep 3

# Check if server is running
if ! kill -0 $PHPSERVER_PID 2>/dev/null; then
    echo "ERROR: Failed to start PHP server"
    cat $PHPSERVER_LOG
    exit 1
fi

echo "PHP server started (PID: $PHPSERVER_PID)"

# Run the shipping tracking tests
echo ""
echo "Running shipping tracking hurl tests..."
echo ""

# Export environment variables for run.sh
export DOLIHOST="$PHPSERVER_HOST"
export DOLIPORT="$PHPSERVER_PORT"
export DOLIUSERNAME="$DOLIUSERNAME"
export DOLIPASSWORD="$DOLIPASSWORD"
export COOKIEJAR="$COOKIEJAR"

# Run tests with filter for shipping tracking
if ! test/hurl/run.sh --cookiefile="$COOKIEJAR" --port="$PHPSERVER_PORT" --host="$PHPSERVER_HOST" --user="$DOLIUSERNAME" --pass="$DOLIPASSWORD" "$@" ; then
    TEST_RESULT=1
else
    TEST_RESULT=0
fi

cleanup

if [ $TEST_RESULT -ne 0 ]; then
    echo "ERROR: Shipping tracking hurl tests failed!"
    exit 1
fi

echo "Shipping tracking hurl tests completed successfully!"
exit 0
