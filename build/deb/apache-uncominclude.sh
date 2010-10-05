#!/bin/sh
# File:		apache-uncominclude.sh
# Changes:	
#      	20010219 Ola Lundqvist <opal@debian.org>
#	20011022 Luca De Vitis <luca@debian.org>
#		Introduced the error variable.
#	        o /[[:space:]][[:space:]]*/[[:space:]]\+/
#	        Changed from "cat $conffile | sed" to "sed ... < $conffile"
#	20020116 Ola Lundqvist <opal@debian.org>
#		Documented the error variable.
# Needs:	$conffile - The file that should be modified.
#		$includefile - The file that should not be included.
# Description:	Comments out a include statement.
# Sets:		$status = {error, nothing, uncomment}
#		$error = error message (if $status = error).

status=error

if [ -z "$conffile" ] ; then
    error="No config file specified in apache-uncominclude.sh"
elif [ -z "$includefile" ] ; then
    error="No include file specified in apache-uncominclude.sh"
elif [ ! -f $conffile ] ; then
    error="File $conffile not found!"
elif [ ! -f $includefile ] ; then
    error="File $includefile not found!"
else
    status=nothing
    if grep -e "^[[:space:]]*#[[:space:]]*Include[[:space:]]\+$includefile\b" $conffile > /dev/null 2>&1; then
	log="${log}Uncommenting import for $includefile in $conffile"
	status=uncomment
	sed -e "s|^\([[:space:]]*\)#\([[:space:]]*Include[[:space:]][[:space:]]*$includefile\b\)|\1\2|g;" < $conffile > $conffile.new
	mv $conffile.new $conffile
    fi
fi
