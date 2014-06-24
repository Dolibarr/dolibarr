#!/bin/bash
# svn.sh - Made for Puppi

# All variables are exported
set -a 

# Sources common header for Puppi scripts
. $(dirname $0)/header || exit 10

# Show help
showhelp () {
    echo "This script performs the svn operations required by puppi::project::svn"
    echo "It integrates and uses variables provided by other core Puppi scripts"
    echo "It has the following options:"
    echo "-a <action> (Optional) What action to perform. Available options: deploy (default), rollback"
    echo "-s <source> (Required) Subversion source repo to use"
    echo "-d <destination> (Required) Directory where files are deployed"
    echo "-u <user> (Optional) User that performs the deploy operations. Default root"
    echo "-su <user> (Optional) Username for access to private svn repo"
    echo "-sp <password> (Optional) Password for access to private svn repo"
    echo "-gs <svn_subdir> (Optional) If only a specific subdir of the svnrepo has to be copied to the install destination"
    echo "-t <tag> (Optional) Tag to deploy"
    echo "-b <branch> (Optional) Branch to deploy"
    echo "-c <commit> (Optional) Commit to deploy"
    echo "-v <true|false> (Optional) If verbose"
    echo "-k <true|false> (Optional) If .svn dir is kept on deploy_root"
    echo "-e <true|false> (Optional) If use export instead of checkout for svn operations"
    echo 
    echo "Examples:"
    echo "svn.sh -a deploy -s $source -d $deploy_root -u $user -gs $svn_subdir -t $tag -b $branch -c $commit -v $bool_verbose -k $bool_keep_svndata"
}

verbose="true"

# Check Arguments
while [ $# -gt 0 ]; do
  case "$1" in
    -a)
      case $2 in
          rollback)
          action="rollback"
          ;;
          *)
          action="install"
          ;;
      esac 
      shift 2 ;;
    -s)
      if [ $source ] ; then
        source=$source
      else
        source=$2
      fi
      shift 2 ;;
    -d)
      if [ $deploy_root ] ; then
        deploy_root=$deploy_root
      else
        deploy_root=$2
      fi
      shift 2 ;;
    -u)
      if [ $user ] ; then
        deploy_user=$user
      else
        deploy_user=$2
      fi
      shift 2 ;;
    -gs)
      if [ $svn_subdir ] ; then
        svn_subdir=$svn_subdir
      else
        svn_subdir=$2
      fi
      shift 2 ;;
    -su)
      if [ $svn_user ] ; then
        svn_user=$svn_user
      else
        svn_user=$2
      fi
      shift 2 ;;
    -sp)
      if [ $svn_password ] ; then
        svn_password=$svn_password
      else
        svn_password=$2
      fi
      shift 2 ;;
    -t)
      if [ $svn_tag ] ; then
        svn_tag=$svn_tag
      else
        svn_tag=$2
      fi
      shift 2 ;;
    -b)
      if [ $branch ] ; then
        branch=$branch
      else
        branch=$2
      fi
      shift 2 ;;
    -c)
      if [ $commit ] ; then
        commit=$commit
      else
        commit=$2
      fi
      shift 2 ;;
    -v)
      if [ $verbose ] ; then
        verbose=$verbose
      else
        verbose=$2
      fi
      shift 2 ;;
    -k)
      if [ $keep_svndata ] ; then
        keep_svndata=$keep_svndata
      else
        keep_svndata=$2
      fi
      shift 2 ;;
    -e)
      if [ $svn_export ] ; then
        svn_export=$svn_export
      else
        svn_export=$2
      fi
      shift 2 ;;
    *)
      showhelp
      exit ;;
  esac
done

if [ "x$verbose" == "xtrue" ] ; then
  verbosity=""
else
  verbosity="--quiet"
fi

cd /

if [ "x$branch" == "xundefined" ] ; then
  branch="trunk"
fi

real_source="$source/$branch"

if [ "x$svn_tag" != "xundefined" ] ; then
  real_source="$source/$svn_tag"
fi

if [ "x$svn_user" != "xundefined" ] && [ "x$svn_password" != "xundefined" ] ; then
  svn_auth="--username=$svn_user --password=$svn_password"
else
  svn_auth=""
fi

svnsubdir=""
svndir=$deploy_root


do_install () {
  if [ "x$keep_svndata" != "xtrue" ] ; then
    if [ ! -d $archivedir/$project-svn ] ; then
      mkdir $archivedir/$project-svn
      chown -R $user:$user $archivedir/$project-svn
    fi
    svndir=$archivedir/$project-svn/svnrepo
  fi
  if [ "x$svn_subdir" != "xundefined" ] ; then
    if [ ! -d $archivedir/$project-svn ] ; then
      mkdir $archivedir/$project-svn
      chown -R $user:$user $archivedir/$project-svn
    fi
    svndir=$archivedir/$project-svn
    svnsubdir="$svn_subdir/"
  fi

  if [ -d $svndir/.svn ] ; then
    cd $svndir
    svn up $verbosity $svn_auth --non-interactive
  else
    svn co $verbosity $real_source $svndir $svn_auth --non-interactive
    cd $svndir
  fi

  if [ "x$svndir" == "x$archivedir/$project-svn" ] ; then
    rsync -a --exclude=".svn" $svndir/$svnsubdir $deploy_root/
  fi
}

do_export () {
  svn export $verbosity $svn_auth --force --non-interactive $real_source/$svn_subdir $deploy_root
}

do_rollback () {
  echo "Rollback not yet supported"
}

# Action!
case "$action" in
    install) 
      if [ "x$svn_export" == "xtrue" ] ; then
        export -f do_export ; su $user -c do_export
      else
        export -f do_install ; su $user -c do_install
      fi
      ;;
    rollback) do_rollback ;;
esac

