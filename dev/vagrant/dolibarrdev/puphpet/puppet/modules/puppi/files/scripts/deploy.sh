#!/bin/bash
# deploy.sh - Made for Puppi

# Sources common header for Puppi scripts
. $(dirname $0)/header || exit 10

# Show help
showhelp () {
    echo "This script deploys the files present in the \$predeploydir to the deploy destination dir"
    echo "It has the following options:"
    echo "\$1 (Required) - Destination directory where to deploy files"
    echo "\$2 (Optional) - Name of the variable that identifies a specific predeploydir"
    echo 
    echo "Examples:"
    echo "deploy.sh /var/www/html/my_app"
    echo "deploy.sh /var/www/html/my_app/conf config"
}

# Check arguments
if [ $1 ] ; then
    deploy_destdir=$1
# This breaks on projects::maven when using more than one deploy destinations
#    [ $deploy_root ] && deploy_destdir=$deploy_root
else
    showhelp
    exit 2 
fi

# Obtain the value of the variable with name passed as second argument
# If no one is given, we take all the files in $predeploydir
if [ $2 ] ; then
    deployfilevar=$2
    deploy_sourcedir="$(eval "echo \${$(echo ${deployfilevar})}")"
    if [ "$deploy_sourcedir" = "" ] ; then
        exit 0
    fi
else
    deploy_sourcedir="$predeploydir"
fi

# Copy files
deploy () {
    case "$debug" in
        yes)
            rsync -rlptDv $deploy_sourcedir/ $deploy_destdir/
            check_retcode
        ;;
        full)
            rsync -rlptDv $deploy_sourcedir/ $deploy_destdir/
            check_retcode
        ;;
        *)
            rsync -rlptD $deploy_sourcedir/ $deploy_destdir/
            check_retcode
        ;;
    esac
}

deploy
