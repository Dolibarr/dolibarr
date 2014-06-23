#!/bin/bash
# git.sh - Made for Puppi

# All variables are exported
set -a 

# Sources common header for Puppi scripts
. $(dirname $0)/header || exit 10

# Show help
showhelp () {
    echo "This script performs the git operations required by puppi::project::git"
    echo "It integrates and uses variables provided by other core Puppi scripts"
    echo "It has the following options:"
    echo "-a <action> (Optional) What action to perform. Available options: deploy (default), rollback"
    echo "-s <source> (Required) Git source repo to use"
    echo "-d <destination> (Required) Directory where files are deployed"
    echo "-u <user> (Optional) User that performs the deploy operations. Default root"
    echo "-gs <git_subdir> (Optional) If only a specific subdir of the gitrepo has to be copied to the install destination"
    echo "-t <tag> (Optional) Tag to deploy"
    echo "-b <branch> (Optional) Branch to deploy"
    echo "-c <commit> (Optional) Commit to deploy"
    echo "-v <true|false> (Optional) If verbose"
    echo "-k <true|false> (Optional) If .git dir is kept on deploy_root"
    echo 
    echo "Examples:"
    echo "git.sh -a deploy -s $source -d $deploy_root -u $user -gs $git_subdir -t $tag -b $branch -c $commit -v $bool_verbose -k $bool_keep_gitdata"
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
      if [ $git_subdir ] ; then
        git_subdir=$git_subdir
      else
        git_subdir=$2
      fi
      shift 2 ;;
    -t)
      if [ $git_tag ] ; then
        git_tag=$git_tag
      else
        git_tag=$2
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
      if [ $keep_gitdata ] ; then
        keep_gitdata=$keep_gitdata
      else
        keep_gitdata=$2
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

gitsubdir=""
gitdir=$deploy_root
if [ "x$keep_gitdata" != "xtrue" ] ; then
  if [ ! -d $archivedir/$project-git ] ; then
    mkdir $archivedir/$project-git
    chown -R $deploy_user:$deploy_user $archivedir/$project-git
  fi
  gitdir=$archivedir/$project-git/gitrepo
fi
if [ "x$git_subdir" != "xundefined" ] ; then
  if [ ! -d $archivedir/$project-git ] ; then
    mkdir $archivedir/$project-git
    chown -R $deploy_user:$deploy_user $archivedir/$project-git
  fi
  gitdir=$archivedir/$project-git
  gitsubdir="$git_subdir/"
fi

do_install () {
  if [ -d $gitdir/.git ] ; then
    cd $gitdir
    git pull $verbosity origin $branch
    git checkout $verbosity $branch
    if [ "x$?" != "x0" ] ; then
      git checkout -b $verbosity $branch
    fi
  else
    git clone $verbosity --branch $branch --recursive $source $gitdir
    cd $gitdir
  fi

  if [ "x$git_tag" != "xundefined" ] ; then
    git checkout $verbosity $git_tag
  fi

  if [ "x$commit" != "xundefined" ] ; then
    git checkout $verbosity $commit
  fi

  if [ "x$gitdir" == "x$archivedir/$project-git" ] ; then
    rsync -a --exclude=".git" $gitdir/$gitsubdir $deploy_root/
  fi

}

do_rollback () {

  echo "Rollback not yet supported"
}

# Action!
case "$action" in
    install) export -f do_install ; su $deploy_user -c do_install ;;
    rollback) do_rollback ;;
esac
