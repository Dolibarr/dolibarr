#!/bin/bash
# wait.sh - Made for Puppi

# Sources common header for Puppi scripts
. $(dirname $0)/header || exit 10

# Show help
showhelp () {
    echo "This script is used to introduce pauses during the deploy workflow"
    echo
    echo "It has the following options:"
    echo "-s <seconds> - The number of seconds to wait"
    echo "-p <filename> - Wait until filename is present"
    echo "-a <filename> - Wait until filename is absent"
    echo "-f <pattern> <filename> - Wait until is found the pattern in the filename"
}

while [ $# -gt 0 ]; do
  case "$1" in
    -s)
      sleep $2
      exit 0
      ;;
    -p)
      while true
         do
            [ -e $2 ] && break
         sleep 1
      done
      exit 0
      ;;
    -a)
      while true
         do
            [ ! -e $2 ] && break
         sleep 1
      done
      exit 0
      ;;
    -f)
      while true
         do
            grep $2 $3 && break
         sleep 1
      done
      exit 0
      ;;
    *)
      showhelp
      exit
      ;;
  esac
done

