#!/bin/sh
# File:		apache-include_all.sh
# Changes:
#      	20010219 Ola Lundqvist <opal@debian.org>
#	20011022 Luca De Vitis <luca@debian.org>
#		Introduced the error variable.
#	        o /[[:space:]][[:space:]]*/[[:space:]]\+/
#	        Reduced the 3 assignment in the function to a single sed
#		script.
#	20020116 Ola Lundqvist <opal@debian.org>
#		Documented the error variable.
#	20020412 Ola Lundqvist <opal@debian.org>
#		Added check for if the server is installed.
# Needs:	$server - what apache server that should be configured.
#			That can be any matching /etc/$server/*.conf
#		$includefile - what file that should not be included.
#		/usr/share/wwwconfig-common/apache-uncominclude.sh
#		/usr/share/wwwconfig-common/apache-include.sh
# Description:	Includes a file in a apache config file.
#		If it is not included (and commented) it will be added
#		at the bottom of the file.
#		It first checks for if any in the server
# Sets:		$status = {error, nothing, uncomment, include}
#		$error = error message (if $status = error).

status=error

if [ -z "$includefile" ] ; then
    error="No include file specified for apache-include_all.sh."
elif [ ! -f $includefile ] ; then
    error="Includefile $includefile not found in apache-include_all.sh."
elif [ ! -d /etc/$server ] ; then
    error="No server $server installed, unable to configure it."
else
    status=nothing
    if grep -e "Include[[:space:]]\+$includefile" /etc/$server/*.conf > /dev/null 2>&1; then
	lstatus=nothing
	log="${log}Include of $includefile found in apache config files."
	for conffile in /etc/$server/*.conf; do
	    . /usr/share/dolibarr/build/deb/apache-uncominclude.sh
	    if [ "$status" = "uncomment" ] ; then
		lstatus=$status
	    fi
	done
	status=$lstatus
    else
	conffile=/etc/$server/httpd.conf
	. /usr/share/dolibarr/build/deb/apache-include.sh
	status=include
    fi
fi
