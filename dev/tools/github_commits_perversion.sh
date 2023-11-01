#/bin/bash
#
# Count number of commits per user and per versions (using date for version detection)
#

Releases=("17.0" "18.0" "develop")
Dates=("2022-08-15", "2023-02-01" "2023-08-31" "2050-01-01")
let "counter = 0"

for i in "${Releases[@]}"
do
  echo "=== Version $i (counter $counter): git shortlog -s -n  --after=${Dates[counter]} --before=${Dates[counter+1]}"
  git shortlog -s -n  --after=${Dates[counter]} --before=${Dates[counter+1]} | tr '[:lower:]' '[:upper:]' > /tmp/github_commits_perversion.txt
  cat /tmp/github_commits_perversion.txt
  echo "Total for version $i:"
  echo -n "- Nb of commits: " 
  git log --pretty=oneline --after=${Dates[counter]} --before=${Dates[counter+1]} | tr '[:lower:]' '[:upper:]' > /tmp/github_commits_perversion2.txt
  cat /tmp/github_commits_perversion2.txt | wc -l
  echo -n "- Nb of different authors: " 
  awk ' { print $2 } ' < /tmp/github_commits_perversion.txt | sort -u | wc -l
  echo "=======================" 
  echo
  let "counter +=1"
done

