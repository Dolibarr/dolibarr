#!/bin/bash
# deploy_files.sh - Made for Puppi
# This is an extended version of the deploy script
# It accepts more options to better handle how files are deployed
# in the destination directory

# Sources common header for Puppi scripts
. $(dirname $0)/header || exit 10

# Show help
showhelp () {
    echo "This script deploys the files present in the \$predeploydir to the deploy destination dir"
    echo "It has the following options:"
    echo "-d <path> (Required) - Destination directory where to deploy files"
    echo "-p <name> (Optional) - Name of the variable that identifies a specific predeploydir"
    echo "-c <true|false> (Default: false) - If to enabled the --delete option to the rsync command"
    echo 
    echo "Examples:"
    echo "deploy_files.sh -d /var/www/html/my_app"
    echo "deploy_files.sh -d /var/www/html/my_app/conf -p config"
    echo "deploy_files.sh -d /var/www/html/my_app/conf -c true"
}

deploy_sourcedir="$predeploydir"
clean_destdir="false"

while [ $# -gt 0 ]; do
  case "$1" in
    -d)
      deploy_destdir=$2
      shift 2 ;;
    -p)
      deployfilevar=$2
      deploy_sourcedir="$(eval "echo \${$(echo ${deployfilevar})}")"
      shift 2 ;;
    -c)
      clean_destdir=$2
      shift 2 ;;
    *)
      showhelp
      exit
      ;;
  esac
done

rsync_delete=""
if [ x$clean_destdir == "xtrue" ] ; then
  rsync_delete="--delete"
fi

# Copy files
deploy () {
    case "$debug" in
        yes)
            rsync -rlptDv $rsync_delete $deploy_sourcedir/ $deploy_destdir/
            check_retcode
        ;;
        full)
            rsync -rlptDv $rsync_delete $deploy_sourcedir/ $deploy_destdir/
            check_retcode
        ;;
        *)
            rsync -rlptD $rsync_delete $deploy_sourcedir/ $deploy_destdir/
            check_retcode
        ;;
    esac
}

deploy
