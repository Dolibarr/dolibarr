#!/bin/bash
#---------------------------------------------------------
# Script to launch LinPhone softphone.
#
# This script can be used to setup a ClickToDial system
# when using LinPhone soft phone with Dolibarr.
#
# More information on https://wiki.dolibarr.org/index.php/Module_ClickToDial_En
#---------------------------------------------------------

# shellcheck disable=2006,2086

# Note: Adding handler into gconf-tools seems to do nothing
# gconftool-2 -t string -s /desktop/gnome/url-handlers/sip/command "linphone-3 -c %s"
# gconftool-2 -s /desktop/gnome/url-handlers/sip/needs_terminal false -t bool
# gconftool-2 -t bool -s /desktop/gnome/url-handlers/sip/enabled true

echo Launch Linphone $1 $2

param=`echo $1 | sed -s 's/^sip:[\/]*//' `

/usr/bin/linphone-3 -c $param &
