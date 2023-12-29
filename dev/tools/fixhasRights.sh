#/bin/bash
#
# Example of script to fix code writing of permissions
#

for f in $(grep -l -e 'user->rights' -R); do
  sed -i -r 's/!empty\(\$user->rights->([_a-z0-9]+)->([_a-z0-9]+)->([_a-z0-9]+)\) *\? *\$user->rights->\1->\2->\3 *: *0;/$user->hasRight("\1", "\2", "\3");/' $f
  sed -i -r 's/ empty\(\$user->rights->([_a-z0-9]+)->([_a-z0-9]+)->([_a-z0-9]+)\) *\? *0 *: *\$user->rights->\1->\2->\3;/ !$user->hasRight("\1", "\2", "\3");/' $f
  sed -i -r 's/!empty\((DolibarrApiAccess::)\$user->rights->([_a-z0-9]+)->([_a-z0-9]+)->([_a-z0-9]+)\)/\1$user->hasRight("\2", "\3", "\4")/g' $f
  sed -i -r 's/!empty\((DolibarrApiAccess::)\$user->rights->([_a-z0-9]+)->([_a-z0-9]+)\)/\1$user->hasRight("\2", "\3")/g' $f
  sed -i -r 's/empty\((DolibarrApiAccess::)\$user->rights->([_a-z0-9]+)->([_a-z0-9]+)->([_a-z0-9]+)\)/!\1$user->hasRight("\2", "\3", "\4")/g' $f
  sed -i -r 's/empty\((DolibarrApiAccess::)\$user->rights->([_a-z0-9]+)->([_a-z0-9]+)\)/!\1$user->hasRight("\2", "\3")/g' $f
  sed -i -r 's/!empty\(\$user->rights->([_a-z0-9]+)->([_a-z0-9]+)->([_a-z0-9]+)\)/$user->hasRight("\1", "\2", "\3")/g' $f
  sed -i -r 's/!empty\(\$user->rights->([_a-z0-9]+)->([_a-z0-9]+)\)/$user->hasRight("\1", "\2")/g' $f
  sed -i -r 's/empty\(\$user->rights->([_a-z0-9]+)->([_a-z0-9]+)->([_a-z0-9]+)\)/!$user->hasRight("\1", "\2", "\3")/g' $f
  sed -i -r 's/empty\(\$user->rights->([_a-z0-9]+)->([_a-z0-9]+)\)/!$user->hasRight("\1", "\2")/g' $f
  sed -i -r 's/\$user->rights\??->([_a-z0-9]+)\??->([_a-z0-9]+)\??->([_a-z0-9]+)/$user->hasRight("\1", "\2", "\3")/g' $f
  sed -i -r 's/\$user->rights\??->([_a-z0-9]+)\??->([_a-z0-9]+)/$user->hasRight("\1", "\2")/g' $f
done

