#!/bin/bash
# get_filesfromlist.sh - Made for Puppi

# Sources common header for Puppi scripts
. $(dirname $0)/header || exit 10

# Show help
showhelp () {
    echo "This script is used to retrieve the files present in a text file list."
    echo "It has 1 required argument:"
    echo "First argument (\$1 - required) is the base url (in URI format) from where to retrieve the files defined in the list"
    echo "The list file is defined as \$downloadedfile , these variables are gathered from the Puppi runtime"
    echo "  config file."
    echo 
    echo "Examples: "
    echo "get_filesfromlist.sh http://svn.example42.com/myproject"
    echo "get_filesfromlist.sh file:///mount/wwwdata/myproject"
    echo "get_filesfromlist.sh ssh://user@server/var/www/myproject"
    echo "get_filesfromlist.sh svn://user:password@server/repo/myproject"
}


if [ $1 ] ; then
    baseurl=$1
    type=$(echo $1 | cut -d':' -f1)
else
    showhelp
    exit 2 
fi


# Download files
downloadfiles () {

    cd $predeploydir

    for file in $(cat $downloadedfile | grep -v "^#" | grep -v "^$" ) ; do
        filepath=$file
        filedir=$(dirname $filepath)
        mkdir -p $filedir
        check_retcode
        
        case $type in 
            ssh|scp) 
                scp "$baseurl:$filepath" $filepath
                check_retcode
            ;;
            http|https|file)
                curl -s -f "$baseurl/$filepath" -o $filepath
                check_retcode
            ;;
            svn)
                svnuri=$(echo $baseurl/$filepath | cut -d'/' -f3-)
                svnusername=$(echo $svnuri | cut -d':' -f1)
                svnpassword=$(echo $svnuri | cut -d':' -f2 | cut -d'@' -f1)
                svnserver=$(echo $svnuri | cut -d'@' -f2 | cut -d'/' -f1)
                svnpath=/$(echo $svnuri | cut -d'@' -f2 | cut -d'/' -f2-)
                mkdir -p $(dirname $svnpath)
                svn export --force --username="$svnusername" --password="$svnpassword" http://$svnserver/$svnpath $(dirname $svnpath)
                check_retcode
            ;;
        esac

    done
}

downloadfiles
