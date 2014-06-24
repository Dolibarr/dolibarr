#!/bin/bash
# archive.sh - Made for Puppi

# Sources common header for Puppi scripts
. $(dirname $0)/header || exit 10

# Show help
showhelp () {
    echo "This script is used to backup or restore contents to/from puppi archivedir"
    echo "It has the following options:"
    echo "-b <backup_source> - Backups the files to be changed in the defined directory"
    echo "-r <recovery_destination> - Recovers file to the provided destination"
    echo "-s <copy|move> - Specifies the backup strategy (move or copy files)"
    echo "-t <tag> - Specifies a tag to be used for the backup"
    echo "-d <variable> - Specifies the runtime variable that defines the predeploy dir"
    echo "-c <yes|no> - Specifies if you want compressed (tar.gz) archives. Default: yes"
    echo "-o 'options' - Specifies the rsync options to use during backup. Use it to specify custom "
    echo "               exclude patterns of files you don't want to archive, for example"
    echo "-m <full|diff> - Specifies the backup type: 'full' backups all the files in backup_source,"
    echo "                 'diff' backups only the files deployed"
    echo "-n <number> - Number of copies of backups to keep on the filesystem. Default 5"
    echo 
    echo "Examples:"
    echo "archive.sh -b /var/www/html/my_app -t html -c yes"
}

# Arguments check
if [ "$#" = "0" ] ; then
    showhelp
    exit
fi

# Default settings
compression=yes
backuptag=all
strategy=copy
backupmethod=full
bakret=5

while [ $# -gt 0 ]; do
  case "$1" in
    -b)
      backuproot=$2
#      [ $deploy_root ] && backuproot=$deploy_root # This is needed to allow override of $deploy_root via puppi cmd. But breaks on puppi::project::maven
      action=backup
      shift 2 ;;
    -r)
      backuproot=$2
#      [ $deploy_root ] && backuproot=$deploy_root # This is needed to allow override of $deploy_root via puppi cmd. But breaks on puppi::project::maven
      action=recovery
      shift 2 ;;
    -t)
      backuptag=$2
      shift 2 ;;
    -s)
      case "$2" in
        mv) strategy="move" ;;
        move) strategy="move" ;;
        *) strategy="copy" ;;
      esac
      shift 2 ;;
    -m)
      case "$2" in
        diff) backupmethod="diff" ;;
        *) backupmethod="full" ;;
      esac
      shift 2 ;;
    -c)
      case "$2" in
        yes) compression="yes" ;;
        y) compression="yes" ;;
        *) compression="none" ;;
      esac
      shift 2  ;;
    -d)
      predeploydir="$(eval "echo \${$(echo $2)}")"
      shift 2 ;;
    -o) 
      rsync_options=$2
      shift 2 ;;
    -n)
      bakret=$2
      shift 2 ;;
    *)
      showhelp
      exit
      ;;
  esac
done


# Backup and Restore functions
backup () {
    mkdir -p $archivedir/$project/$tag/$backuptag
    if [ $archivedir/$project/latest ] ; then
        rm -f $archivedir/$project/latest
    fi
    ln -sf $archivedir/$project/$tag $archivedir/$project/latest

    filelist=$storedir/filelist
    cd $predeploydir
    find . | cut -c 3- | grep -v "^$" > $filelist

    if [ "$strategy" = "move" ] ; then 
        for file in $(cat $filelist) ; do
            mv $backuproot/$file $archivedir/$project/$tag/$backuptag/
        done
        if [ "$backupmethod" = "full" ] ; then
            rsync -a $rsync_options $backuproot/ $archivedir/$project/$tag/$backuptag/
        fi
    else
        if [ "$backupmethod" = "full" ] ; then
            rsync -a $rsync_options $backuproot/ $archivedir/$project/$tag/$backuptag/
        else
            rsync -a $rsync_options --files-from=$filelist $backuproot/ $archivedir/$project/$tag/$backuptag/
        fi
    fi

    if [ "$compression" = "yes" ] ; then
        cd $archivedir/$project/$tag/$backuptag/
        tar -czf ../$backuptag.tar.gz .
        cd $archivedir/$project/$tag/
        rm -rf $archivedir/$project/$tag/$backuptag/
    fi
}

recovery () {
    if [ ! $rollbackversion ] ; then
        echo "Variable rollbackversion must exist!"
        exit 2 
    fi

    if [ -d $archivedir/$project ] ; then
        cd $archivedir/$project
    else 
        echo "Can't find archivedir for this project"
        exit 2
    fi

    if [ "$compression" = "yes" ] ; then
        cd $backuproot/
        tar -xzf $archivedir/$project/$rollbackversion/$backuptag.tar.gz .
    else 
        rsync -a $rsync_options $rollbackversion/$backuptag/* $backuproot
    fi

}

delete_old () {
    # We don't count the "latest" symlink
    bakret=$(expr $bakret + 1 )

    cd $archivedir/$project

    ddirs=$(ls -1p 2>/dev/null | wc -l)
    while [ $ddirs -gt $bakret ]
    do
        victim=$(ls -tr 2>/dev/null | head -1)
        rm -rf $victim && echo "Deleted old $archivedir/$project/$victim"
        ddirs=$(ls -1p 2>/dev/null | wc -l)
    done
}

# Action!
case "$action" in
    backup) backup ; delete_old ;;
    recovery) recovery ;;
esac
