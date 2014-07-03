#!/bin/bash
# get_maven_files.sh - Made for Puppi
# This script retrieves the files to deploy from a Maven repository.
# It uses variables defined in the general and project runtime configuration files.
# It uses curl to retrieve files so the $1 argument (base url of the maven repository) 
# has to be in curl friendly format
#   It has the following options:
#   -u <http_user> - in case of type http, specify a http_user for curl
#   -p <http_password> - in case of type http, specifiy http_user for curl

# Sources common header for Puppi scripts
. $(dirname $0)/header || exit 10

while [ $# -gt 0 ]; do
  case "$1" in
    -u)
      http_user=$2
      shift 2 ;;
    -p)
      http_password=$2
      shift 2 ;;
    *)
      url=$1
      ftype=$2
      shift 2 ;;
  esac
done

# Obtain the value of the variable with name passed as second argument
# If no one is given, we take all the files in storedir

#echo "Download and deploy $2 ? (Y/n)" 
#read press
#case $press in 
#    Y|y) true ;;
#    N|n) save_runtime_config "predeploydir_$2=" ; exit 0
#esac

if [ $debug ] ; then
    tarcommand="tar -xvf"
else
    tarcommand="tar -xf"
fi

if [ $debug ] ; then
    zipcommand="unzip"
else
    zipcommand="unzip -q"
fi

cd $storedir

if [ -z "$http_password" ] ; then
    authparam=""
else
    authparam="--anyauth --user $http_user:$http_password"
fi

case $ftype in
    warfile)
        curl -s -f $authparam "$url/$version/$warfile" -O
        check_retcode
        cp -a $warfile $predeploydir/$artifact.war
        save_runtime_config "deploy_warpath=$deploy_root/$artifact.war"
    ;;
    jarfile)
        curl -s -f $authparam "$url/$version/$jarfile" -O
        check_retcode
        cp -a $jarfile $predeploydir/$artifact.jar
        save_runtime_config "deploy_jarpath=$deploy_root/$artifact.jar"
    ;;
    configfile)
        curl -s -f $authparam "$url/$version/$configfile" -O
        check_retcode
        mkdir $workdir/$project/deploy_configfile
        cd $workdir/$project/deploy_configfile
        $tarcommand $storedir/$configfile
        check_retcode
        save_runtime_config "predeploydir_configfile=$workdir/$project/deploy_configfile"
    ;;
    srcfile)
        curl -s -f $authparam "$url/$version/$srcfile" -O
        check_retcode
        mkdir $workdir/$project/deploy_srcfile
        cd $workdir/$project/deploy_srcfile
        $tarcommand $storedir/$srcfile
        check_retcode
        save_runtime_config "predeploydir_srcfile=$workdir/$project/deploy_srcfile"
    ;;
    zipfile)
        curl -s -f $authparam "$url/$version/$zipfile" -O
        check_retcode
        mkdir $workdir/$project/deploy_zipfile
        cd $workdir/$project/deploy_zipfile
        $zipcommand $storedir/$zipfile
        check_retcode
        save_runtime_config "predeploydir_zipfile=$workdir/$project/deploy_zipfile"
    ;;
esac
