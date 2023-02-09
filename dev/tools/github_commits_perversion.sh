#/bin/bash
#
# Count number of commits per user and per versions (using date for version detection)
#

Releases=("16.0" "develop")
Dates=("2022-01-01" "2022-08-31" "2050-01-01")
let "counter = 1"

for i in "${Releases[@]}"
do
  echo "=== $counter git shortlog -s -n  --after=${Dates[counter-1]} --before=${Dates[counter]}"
  git shortlog -s -n  --after=${Dates[counter-1]} --before=${Dates[counter]}
  echo -n "Total $i: " 
  git log --pretty=oneline --after=${Dates[counter-1]} --before=${Dates[counter]} | wc -l
  echo "=======================" 
  echo
  let "counter +=1"
done

