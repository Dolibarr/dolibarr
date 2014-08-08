#!/usr/bin/env bash

# usage is: validate_db_connection 2 50 psql

SLEEP=$1
TRIES=$2
PSQL=$3

STATE=1

for (( c=1; c<=$TRIES; c++ ))
do
  echo $c
  if [ $c -gt 1 ]
  then
    echo 'sleeping'
    sleep $SLEEP
  fi

  /bin/echo "SELECT 1" | $PSQL
  STATE=$?

  if [ $STATE -eq 0 ]
  then
    exit 0
  fi
done

echo 'Unable to connect to postgresql'

exit 1
