#!/bin/sh
#----------------------------------------------------
# Script to resize browser window to 1280x1024 to
# be able to make size fixed screenshots using
# ALT+Print screen.
#----------------------------------------------------

# Syntax
if [ "x$1" = "x" ]
then
	echo "resize_windows.sh (list|0x99999999)"
fi

# To list all windows
if [ "x$1" = "xlist" ]
then
	wmctrl -l
fi

# To resize a specific window
if [ "x$1" != "xlist" -a "x$1" != "x" ]
then
	wmctrl -i -r $1 -e 0,0,0,1280,1024
	echo Size of windows $1 modified
fi

