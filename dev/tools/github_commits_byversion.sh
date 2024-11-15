#!/bin/bash
# Copyright (C) 2024		MDW	<mdeweerd@users.noreply.github.com>

#
# Count number of commits per user and per version (using date for version detection)
#
#
#

echo "Copy script into /tmp/github_commits_byversion.sh"
cp "$0" /tmp/github_commits_perversion.sh

TEMP_DIR=/tmp/git
DOL_GIT="$TEMP_DIR/dolibarr"
if ! git rev-parse ; then
	echo "Delete $TEMP_DIR"
	rm -fr "$TEMP_DIR"
	echo "Create '$TEMP_DIR' and cd to it"
	mkdir "$TEMP_DIR"
	cd "$TEMP_DIR" || exit
	git clone https://github.com/Dolibarr/dolibarr.git
	cd "${DOL_GIT}" || exit
else
	if [ -r "${DOL_GIT}" ] ; then
		git worktree remove "${DOL_GIT}"
		rm -rf "${DOL_GIT}" >& /dev/null
	fi
	git worktree add --force "${DOL_GIT}" develop
	cd "$DOL_GIT" || exit
	git pull
fi


# Determine release to check
Releases=("3.9" "4.0" "5.0" "6.0" "7.0" "8.0" "9.0" "10.0" "11.0" "12.0" "13.0" "14.0" "15.0" "16.0" "17.0" "18.0" "19.0" "20.0")
target_version=$(sed -n "s/.*define('DOL_VERSION',[[:space:]]*'\\([0-9]*\\.[0-9]*\\).*/\\1/p" htdocs/filefunc.inc.php)

# Default target version in case getting it from filefunc.inc failed
target_version=${target_version:=20.0}

# Setup loop to append required versions
target_major=${target_version%%.*}
last_major=${Releases%%.*}

# Add versions up to target_version
while (( last_major < target_major )); do
	((last_major++))
	tag="${last_major}.0"
	if git rev-parse --verify "origin/$tag" >&/dev/null ; then
		Releases+=("$tag")
	fi
done

# Always end with develop
Releases+=("develop")


# Now proceed with generating the report for branches in ${Releases[@]}

firstline=1
((counter = 0))
for i in "${Releases[@]}"
do
	if [ $firstline -eq 1 ]; then
		firstline=0
		continue
	fi

	#echo "=== Version $i (counter $counter):"
	echo "=== Version $i (counter $counter):"
	echo "Get common commit ID between origin/${Releases[counter]} and origin/${Releases[counter+1]}"
	echo "git merge-base origin/${Releases[counter]} origin/${Releases[counter+1]}"
	commitidcommon=$(git merge-base "origin/${Releases[counter]}" "origin/${Releases[counter+1]}")
	echo "Found commitid=$commitidcommon"

	echo "Checkout version $i"
	git checkout --ignore-other-worktrees "$i"
	#git shortlog -s -n  --after=YYYY-MM-DD --before=YYYY-MM-DD | tr '[:lower:]' '[:upper:]' > /tmp/github_commits_perversion.txt
	git shortlog --encoding=utf-8 -s -n "$commitidcommon.."  | iconv -f UTF-8 -t ASCII//TRANSLIT | tr '[:lower:]' '[:upper:]' > /tmp/github_commits_perversion.txt
	#cat /tmp/github_commits_perversion.txt
	echo "Total for version $i:"
	echo -n "- Nb of commits: "
	git log "$commitidcommon.." --pretty=oneline | tr '[:lower:]' '[:upper:]' > /tmp/github_commits_perversion2.txt
	wc -l < /tmp/github_commits_perversion2.txt
	echo -n "- Nb of different authors: "
	awk ' { print $2 } ' < /tmp/github_commits_perversion.txt | sort -u | wc -l
	echo "======================="
	echo
	((counter++))
done

# Clean up git directory if it is a worktree
if [ "$(git rev-parse --git-dir)" != "$(git rev-parse --git-common-dir)" ] ; then
	cd "$TEMP_DIR" || exit
	git -C "$DOL_GIT" worktree remove "$DOL_GIT"
fi
exit
