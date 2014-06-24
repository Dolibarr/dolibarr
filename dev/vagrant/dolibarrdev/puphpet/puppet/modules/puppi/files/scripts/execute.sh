#!/bin/bash
# execute.sh - Made for Puppi
# This script just executes what is passed as argument

# Sources common header for Puppi scripts
. $(dirname $0)/header || exit 10

#parse variables
command=$(eval "echo "$*"")

#execute command
eval "${command}"
