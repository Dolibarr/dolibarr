#!/bin/bash
#---------------------------------------------------------
# Script to launch LinPhone softphone.
# This script can be used to setup a ClickToDial system
# when using LinPhone soft phone.
# More information on http://wiki.dolibarr.org/index.php/Module_ClickToDial_En
#---------------------------------------------------------

echo Launch Linphone $1 $2
param=`echo $1 | sed -s 's/^sip:[\/]*//' `
/usr/bin/linphone-3 -c $param
