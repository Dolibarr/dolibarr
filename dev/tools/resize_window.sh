#!/bin/sh
#----------------------------------------------------
# Script to resize browser window to 1280x1024 to
# be able to make size fixed screenshots using
# ALT+Print screen.
#----------------------------------------------------
# shellcheck disable=2086,2166,2268

# Syntax
if [ "x$1" = "x" ]
then
	echo "resize_windows.sh (list|0x99999999) [1280 1024]"
fi

# To list all windows
if [ "x$1" = "xlist" ]
then
	wmctrl -l
fi

# To resize a specific window
if [ "x$1" != "xlist" -a "x$1" != "x" ]
then
	if [ "x$2" = "x" ]
	then
		width=1280
	else
		width=$2
	fi
	if [ "x$3" = "x" ]
	then
		height=1024
	else
		height=$3
	fi
	wmctrl -i -r $1 -e 0,0,0,$width,$height
	echo Size of windows $1 modified to $width x $height
fi

