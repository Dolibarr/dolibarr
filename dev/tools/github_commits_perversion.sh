#/bin/bash
Releases=("3.8" "3.9" "4.0" "5.0", "develop")
Dates=("2010-01-01", "2011-01-01", "2012-01-01", "2013-01-01", "2014-01-01", "2015-01-01", "2016-07-01")
let "counter = 1"

for i in "${Releases[@]}"
do
  git shortlog -s -n  --after=${Dates[counter-1]} --before=${Dates[counter]}
  echo -n "Total $i: " 
  git log --pretty=oneline --after=${Dates[counter-1]} --before=${Dates[counter]} | wc -l
  echo "=======================" 
  echo
  let "counter +=1"
done

