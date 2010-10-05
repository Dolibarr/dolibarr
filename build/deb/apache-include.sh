#!/bin/sh
# File:		apache-include.sh
# Changes:
#      	20010219 Ola Lundqvist <opal@debian.org>
#	20011022 Luca De Vitis <luca@debian.org>
#		Introduced the error variable.
#	        o /[[:space:]][[:space:]]*/[[:space:]]\+/
#	20020116 Ola Lundqvist <opal@debian.org>
#		Documented the error variable.
# Needs:	$conffile - where the config file is.
#		$includefile - what file that should not be included.
#		/usr/share/wwwconfig-common/apache-uncominclude.sh
# Description:	Includes a file in a apache config file.
#		If it is not included (and commented) it will be added
#		at the bottom of the file.
# Sets:		$status = {error, nothing, include, uncomment}
#		$error = error message (if $status = error).

status=error
if [ -z "$conffile" ] ; then
    error="No config file specified in apache-include.sh"
elif [ -z "$includefile" ] ; then
    error="No include file specified in apache-include.sh"
elif [ ! -f $conffile ] ; then
    error="File $conffile not found!"
elif [ ! -f $includefile ] ; then
    error="File $includefile not found!"
else
    status=nothing
    . /usr/share/dolibarr/build/deb/apache-uncominclude.sh
    if [ "$status" = "nothing" ] ; then
	if ! grep -e "Include[[:space:]]\+$includefile\b" $conffile > /dev/null 2>&1; then
	    status=include
	    log="${log}Including $includefile in $conffile."
	    echo "Include $includefile" >> $conffile
	fi
    fi
fi
