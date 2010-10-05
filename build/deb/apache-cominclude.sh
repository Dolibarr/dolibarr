#!/bin/sh
# File:		apache-cominclude.sh
# Changes:
#	20010219 Ola Lundqvist <opal@debian.org>
#	20011022 Luca De Vitis <luca@debian.org>
#		Introduced the error variable.
#	        o /[[:space:]][[:space:]]*/[[:space:]]\+/
#		Changed from "cat $conffile | sed" to "sed ... < $conffile"
#	20020116 Ola Lundqvist <opal@debian.org>
#		Documented the error variable.
# Needs:	$conffile - The file that should be modified.
#		$includefile - The file that should be commented out.
# Description:	Comments out a include statement.
# Sets:		$status = {error, nothing, comment}
#		$error = error message (if $status = error).

status=error

if [ -z "$conffile" ] ; then
    error="No config file specified in apache-cominclude.sh"
elif [ -z "$includefile" ] ; then
    error="No include file specified in apache-cominclude.sh"
elif [ ! -f $conffile ] ; then
    error="File $conffile not found!"
elif [ ! -f $includefile ] ; then
    error="File $includefile not found!"
else
    status=nothing
    if grep -e "^[[:space:]]*Include[[:space:]]\+$includefile\b" $conffile > /dev/null 2>&1; then
	log="${log}Commenting import for $includefile in $conffile."
	status=comment
	sed -e "s#^\( *\)\(Include *$includefile\b\)#\1\#\2#" < $conffile > $conffile.new
	mv $conffile.new $conffile
    fi
fi
