#!/bin/sh
# Copyright (C) 2014 Raphaël Doursenaud
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.

VERSION=0.0.1
USAGE="Usage: newmodule.sh NewName"

# TODO: check depedencies presence (find, sed and rename)
# TODO: allow execution from build directory
# TODO: validate parameter
# TODO: use multiple word parameter, for example "My module is awesome" which should lead to "MyModuleIsAwesome" and "mymoduleisawesome" so we can also fix language strings
# TODO: add module ID management (language files…)
# TODO: add oneliner description management
# TODO: add copyright management

if [ $# == 0 ] ; then
    echo ${USAGE}
    exit 1;
fi

ToLower () {
	echo $(echo $1 | tr '[:upper:]' '[:lower:]')
}
ToUpper () {
	echo $(echo $1 | tr '[:lower:]' '[:upper:]')
}

CAMELORIG="MyModule"
LOWERORIG=$(ToLower ${CAMELORIG})
UPPERORIG=$(ToUpper ${CAMELORIG})
cameltarget=$(echo $1)
lowertarget=$(ToLower $1)
uppertarget=$(ToUpper $1)
thisscript=`basename $0`

# Rewrite occurences
find . -not -iwholename '*.git*' -not -name "${thisscript}" -type f -print0 | xargs -0 sed -i'' -e"s/${CAMELORIG}/${cameltarget}/g"
find . -not -iwholename '*.git*' -not -name "${thisscript}" -type f -print0 | xargs -0 sed -i'' -e"s/${LOWERORIG}/${lowertarget}/g"
find . -not -iwholename '*.git*' -not -name "${thisscript}" -type f -print0 | xargs -0 sed -i'' -e"s/${UPPERORIG}/${uppertarget}/g"

# Rename files
for file in $(find . -not -iwholename '*.git*' -name "*${CAMELORIG}*" -type f)
do
	rename ${CAMELORIG} ${cameltarget} ${file}
done
for file in $(find . -not -iwholename '*.git*' -name "*${LOWERORIG}*" -type f)
do
	rename ${LOWERORIG} ${lowertarget} ${file}
done
for file in $(find . -not -iwholename '*.git*' -name "*${UPPERORIG}*" -type f)
do
	rename ${UPPERORIG} ${uppertarget} ${file}
done

# TODO: add instructions about renaming vars (ack --php -i my)
# TODO: add instructions about renaming files (ls -R|grep -i my)
