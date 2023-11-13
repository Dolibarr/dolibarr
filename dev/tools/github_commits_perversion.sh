#/bin/bash
#
# Count number of commits per user and per versions (using date for version detection)
#

Releases=("3.9" "4.0" "5.0" "6.0" "7.0" "8.0" "9.0" "10.0" "11.0" "12.0" "13.0" "14.0" "15.0" "16.0" "17.0" "18.0" "develop")
let "counter = 0"

echo "Copy script into /tmp/github_commits_perversion.sh"
cp $0 /tmp/github_commits_perversion.sh

echo "Delete /tmp/git"
rm -fr /tmp/git
echo "Create and go into /tmp/git"
mkdir /tmp/git
cd /tmp/git
git clone https://github.com/Dolibarr/dolibarr.git

cd /tmp/git/dolibarr

firstline=1
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
  commitidcommon=`git merge-base origin/${Releases[counter]} origin/${Releases[counter+1]}`
  echo "Found commitid=$commitidcommon"
  
  echo "Checkout into version $i"
  git checkout $i  
  #git shortlog -s -n  --after=YYYY-MM-DD --before=YYYY-MM-DD | tr '[:lower:]' '[:upper:]' > /tmp/github_commits_perversion.txt
  git shortlog -s -n $commitidcommon.. | tr '[:lower:]' '[:upper:]' > /tmp/github_commits_perversion.txt
  #cat /tmp/github_commits_perversion.txt
  echo "Total for version $i:"
  echo -n "- Nb of commits: " 
  git log $commitidcommon.. --pretty=oneline | tr '[:lower:]' '[:upper:]' > /tmp/github_commits_perversion2.txt
  cat /tmp/github_commits_perversion2.txt | wc -l
  echo -n "- Nb of different authors: " 
  awk ' { print $2 } ' < /tmp/github_commits_perversion.txt | sort -u | wc -l
  echo "=======================" 
  echo
  let "counter +=1"
done

