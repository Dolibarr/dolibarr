#!/bin/bash
# predeploy.sh - Made for Puppi

# Sources common header for Puppi scripts
. $(dirname $0)/header || exit 10

# Show help
showhelp () {
    echo "This script prepares the contents of the predeploy directory"
    echo "It integrates and uses variables provided by other core Puppi scripts"
    echo "It has the following options:"
    echo "-v <variable_name> (optional) Name of the variable that identifies the file to untar/unzip"
    echo "                              By default is used $downloadedfile"
    echo "-t <file_type> (optional) The type of file that is retrieved: zip|tarball"
    echo "                          By default is used $source_type "
    echo "-m <magicfix> (optional) The prefix (directory) you may not want to put in the deploy"
    echo "                       Use this if the zip or tar contain a base dir (as often) and you want to copy"
    echo "                       to the deploy dir only its contents and not the whole directory"
    echo 
    echo "Examples:"
    echo "predeploy.sh "
    echo "predeploy.sh -t zip"
    echo "predeploy.sh -t zip -v myz"
}


# Check Arguments
while [ $# -gt 0 ]; do
  case "$1" in
    -v)
      downloadedfile="$(eval "echo \${$(echo ${2})}")"
      shift 2 ;;
    -t)
      source_type=$2
      shift 2 ;;
    -m)
      predeploy_dirprefix=$2
      shift 2 ;;
  esac
done



predeploy () {
    cd $predeploydir
    case "$source_type" in
      tarball)
        case "$debug" in
          yes|full)
            tar -zxvf $downloadedfile
            check_retcode
          ;;
          *)
            tar -zxf $downloadedfile
            check_retcode
          ;;
        esac
        ;;
      zip)
        case "$debug" in
          yes|full)
            unzip $downloadedfile
            check_retcode
          ;;
          *)
            unzip -qq $downloadedfile
            check_retcode
          ;;
        esac
        ;;
      gz)
        case "$debug" in
          yes|full)
            gzip -d $downloadedfile
            check_retcode
          ;;
          *)
            gzip -d -q $downloadedfile
            check_retcode
          ;;
        esac
        ;;
      war)
        cp $downloadedfile .
        check_retcode
        ;;
    esac
}

predeploy

# Updates predeploydir if a directory prefix exists
if [[ x$predeploy_dirprefix != "x" ]] ; then
   save_runtime_config "predeploydir=$predeploydir/$predeploy_dirprefix"
fi
