#!/bin/bash
#---------------------------------------------------------
# Script to launch Ekiga softphone.
# This script can be used to setup a ClickToDial system
# when using Ekiga soft phone with Dolibarr.
# More information on https://wiki.dolibarr.org/index.php/Module_ClickToDial_En
#---------------------------------------------------------

ekiga -c "$1" &

