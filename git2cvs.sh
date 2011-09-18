#!/bin/sh
######################################################################
#
# git2cvs
#
# Commit git commits to CVS.
#
# See:
# http://vwelch.blogspot.com/2009/10/using-git-with-cvs-repository.html
#
######################################################################

# Exit on any error
#set -e

# Tag name used to indicate last commit exported to CVS
tag_name="CVS-LAST-EXPORT-3.1"
branch="3.1"
export CVS_DIR="/media/DATA/Mes Developpements/dolibarroldcvs"
export GIT_DIR="/home/ldestail/git/dolibarrold/.git"

cd "$CVS_DIR"

# Load name of git directory from ./.git-dir
if test -f .git-dir ; then
    export GIT_DIR=`cat ./.git-dir`
fi
echo "GIT_DIR is ${GIT_DIR}"


# Sanity check: look for tag
git tag | grep ${tag_name} > /dev/null
if test $? -ne 0 ; then
    echo "Did not find expected tag ${tag_name} in git repository."
    exit 1
fi

echo "Getting list of commits since last export..."
# 'cut' remove log message, leaving commit id
# 'tail -r' reverses to commits in chronological order
# git log --pretty=oneline 644b496732.. 3.1-beta | cut -f 1 -d " " | tac
commits=`git log --pretty=oneline ${tag_name}.. ${branch} | cut -f 1 -d " " | tac`

num_commits=`echo ${commits} | wc -w`
echo "Found ${num_commits} commits."

for commit in ${commits} ; do
    echo "Commiting ${commit} to CVS: git cvsexportcommit -c -v ${commit}"
    # It would be nice to display commit message here, but I can't
    # figure out how to do it.
    git cvsexportcommit -c -v ${commit}
    echo "Result=$?"
    echo "Moving tag..."
    git tag -f ${tag_name} ${commit}
    read ligne
done

echo "Success."
exit 0
