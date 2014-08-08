#!/bin/bash
# get_file.sh - Made for Puppi

# Sources common header for Puppi scripts
. $(dirname $0)/header || exit 10

# Show help
showhelp () {
    echo "This script is used to retrieve the file defined after the -s parameter"
    echo "The source specified  can be any of these:"
    echo "  file://local/file/path"
    echo "  http(s)://my.server/file/path"
    echo "  ssh://user@my.server/file/path"
    echo "  svn://user:password@my.server/file/path"
    echo "Note: Avoid using chars like : / and @ outside the Uri standards paths"
    echo
    echo "It has the following options:"
    echo "-s <source_file> - The URL of the file to get"
    echo "-t <file_type> - The type of file that is retrieved: list|tarball|maven-metadata|dir"
    echo "-d <local_dir> - An alternative destination directory (default is automatically chosen)"
    echo "-a <yes|no> - If 'no' return a special error code (99) if the download checksum is the same of the one previously downloaded"
    echo "-u <http_user> - in case of type http, specify a http_user for curl"
    echo "-p <http_password> - in case of type http, specifiy http_user for curl"
    echo "-k - tell curl not to validate ssl certs"
    echo "              This option can be used for automatic deploys (ie via cron) that actually deploy only new changes"
}

while [ $# -gt 0 ]; do
  case "$1" in
    -s)
      type=$(echo $2 | cut -d':' -f1)
      url=$2
      downloadfilename=$(basename $2)
      downloaddir=$predeploydir
      shift 2 ;;
    -t)
      case $2 in
      # This logic is applied:
      # In $predeploydir go ($workdir/$project/deploy) go file that have to be deployed
      # In $storedir go ($workdir/$project/store) go support files as tarballs or lists
          list)
          downloaddir=$storedir
          save_runtime_config "source_type=list"
          ;;
          tarball)
          downloaddir=$storedir
          save_runtime_config "source_type=tarball"
          ;;
          tar)
          downloaddir=$storedir
          save_runtime_config "source_type=tar"
          ;;
          zip)
          downloaddir=$storedir
          save_runtime_config "source_type=zip"
          ;;
          maven-metadata)
          downloaddir=$storedir
          save_runtime_config "source_type=maven"
          ;;
          dir)
          downloaddir=$predeploydir
          save_runtime_config "source_type=dir"
          ;;
          war)
          downloaddir=$predeploydir
          save_runtime_config "source_type=war"
          ;;
          mysql)
          downloaddir=$storedir
          save_runtime_config "source_type=mysql"
          ;;
          gz)
          downloaddir=$storedir
          save_runtime_config "source_type=gz"
          ;;
      esac
      shift 2 ;;
    -d)
      # Enforces and overrides and alternative downloaddir
      downloaddir=$2
      shift 2 ;;
    -a)
      alwaysdeploy=$2
      shift 2 ;;
    -u)
      http_user=$2
      shift 2 ;;
    -p)
      http_password=$2
      shift 2 ;;
    -k)
      ssl_arg=$1
      shift 1 ;;
    *)
      showhelp
      exit
      ;;
  esac
done

# Define what to use for downloads
cd $downloaddir

case $type in
    s3)
        s3cmd get $url
        check_retcode
        save_runtime_config "downloadedfile=$downloaddir/$downloadfilename"
    ;;
    ssh|scp) 
        # ssh://user@my.server/file/path
        scpuri=$(echo $url | cut -d'/' -f3-)
        scpconn=$(echo $scpuri | cut -d'/' -f1)
        scppath=/$(echo $scpuri | cut -d'/' -f2-)
        rsync -rlptD -e ssh $scpconn:$scppath .
        check_retcode
        save_runtime_config "downloadedfile=$downloaddir/$downloadfilename"
    ;;
    http|https)
        if [ -z "$http_password" ] ; then
          curl $ssl_arg -s -f -L "$url" -O
        else
          curl $ssl_arg -s -f -L --anyauth --user $http_user:$http_password "$url" -O
	fi
        check_retcode
        save_runtime_config "downloadedfile=$downloaddir/$downloadfilename"
    ;;
    svn)
        svnuri=$(echo $url | cut -d'/' -f3-)
        svnusername=$(echo $svnuri | cut -d':' -f1)
        svnpassword=$(echo $svnuri | cut -d':' -f2 | cut -d'@' -f1)
        svnserver=$(echo $svnuri | cut -d'@' -f2 | cut -d'/' -f1)
        svnpath=/$(echo $svnuri | cut -d'@' -f2 | cut -d'/' -f2-)
        mkdir -p $(dirname $svnpath)
        svn export --force --username="$svnusername" --password="$svnpassword" svn://$svnserver/$svnpath $downloaddir
        check_retcode
        save_runtime_config "downloadedfile=$downloaddir/$downloadfilename"
    ;;
    file)
        # file:///file/path
        filesrc=$(echo $url | cut -d '/' -f3-)
        rsync -rlptD $filesrc .
        check_retcode
        save_runtime_config "downloadedfile=$downloaddir/$downloadfilename"
    ;;
    rsync)
        rsync -a "$url" .
        # rsync -rlptD $url . # Why not preserving users/groups?
        check_retcode
        save_runtime_config "downloadedfile=$downloaddir/$downloadfilename"
    ;;

esac

if [ x$alwaysdeploy == "xno" ] ; then
    # Here is checked the md5sum of the downloaded file against a previously save one
    # If the sums are the same the scripts exits 99 and puppi will stop the deploy without any warning or notification
    [ -d $archivedir/$project ] || mkdir -p $archivedir/$project
    touch $archivedir/$project/md5sum
    md5sum $downloaddir/$downloadfilename > $workdir/$project/md5sum_downloaded
    cat $archivedir/$project/md5sum > $workdir/$project/md5sum_deployed
    diff $workdir/$project/md5sum_downloaded $workdir/$project/md5sum_deployed && exit 99
    md5sum $downloaddir/$downloadfilename > $archivedir/$project/md5sum
fi
