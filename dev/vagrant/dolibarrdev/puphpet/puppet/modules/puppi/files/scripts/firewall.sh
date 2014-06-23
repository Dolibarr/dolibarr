#!/bin/bash
# firewall.sh - Made for Puppi

# Sources common header for Puppi scripts
. $(dirname $0)/header || exit 10

# Show help
showhelp () {
    echo "This script places a temporary firewall (iptables) rule to block access from the IP defined"
    echo "It has the following options:"
    echo "\$1 (Required) - Remote Ip address to block (Generally a load balancer"
    echo "\$2 (Required) - Local port to block (0 for all ports"
    echo "\$3 (Required) - Set on or off to insert or remove the blocking rule"
    echo "\$4 (Required) - Number of seconds to sleep after having set the rule"
    echo 
    echo "Examples:"
    echo "firewall.sh 10.42.0.1 0 on"
    echo "firewall.sh 10.42.0.1 0 off"
}

# Check arguments
if [ $2 ] ; then
    ip=$1
    port=$2
else
    showhelp
    exit 2 
fi

if [ $3 ] ; then
    if [ "$3" = "on" ] ; then
        action="-I"
    elif [ "$3" = "off" ] ; then
        action="-D"
    else 
        showhelp
        exit 2
    fi
else
    showhelp
    exit 2
fi

if [ $4 ] ; then
    delay=$4
else
    delay="1"
fi

# Block
run_iptables () {
    if [ "$port" = "0" ] ; then
        iptables $action INPUT -s $ip -j DROP
    else
        iptables $action INPUT -s $ip -p tcp --dport $port -j DROP
    fi
}

run_iptables
echo "Sleeping for $delay seconds"
sleep $delay

# Sooner or later this script will have multiOS support
