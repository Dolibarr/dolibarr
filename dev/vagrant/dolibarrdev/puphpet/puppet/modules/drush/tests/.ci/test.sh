#!/bin/bash

echo "Creating test environment..."
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
if [ -e $SCRIPT_DIR/.module ]
then
  MODULE=`cat $SCRIPT_DIR/.module`
else
  echo "ERROR: The test script expects the name of the module to be in a file"
  echo "       called '.module', in the same directory as the test script."
  echo $SCRIPT_DIR
  exit 1
fi
cd $SCRIPT_DIR
cd ../..
if [ -e manifests/init.pp ]
then
  MODULE_DIR=`pwd`
else
  echo "ERROR: The test script expects to be in <module_dir>/tests/.ci/, but"
  echo "       cannot find the module's 'init.pp', from its current location."
  echo $SCRIPT_DIR
  exit 1
fi
rm -rf /tmp/$MODULE
cp $MODULE_DIR /tmp/$MODULE -r
cd /tmp/$MODULE
wget http://ansi-color.googlecode.com/svn/tags/0.6/ansi-color/color >> /dev/null 2>&1
mv ./color /usr/local/bin
chmod a+x /usr/local/bin/color

echo "Scanning for tests in '$MODULE' module..."
FILES=`find /tmp/$MODULE/tests -name *.pp`
COUNT=${#FILES[@]}
PASSED=0
FAILED=0
TOTAL=0

echo "Running tests..."
for f in $FILES
do
  NAME=`basename $f`
  echo "Running '$NAME' test..."
  OUTPUT=`puppet apply --noop --modulepath=/tmp/ --color=ansi $f 2>&1`
  STATUS=$?
  if [ $STATUS -ne 0 ]
  then
    color red
    echo "///////////////////////////////////////////////////////"
    echo
    echo "    ERROR in '$NAME' test."
    echo "    Output from failed test:"
    echo
    echo $OUTPUT
    echo
    echo "///////////////////////////////////////////////////////"
    color off
    let FAILED++
    let TOTAL++
  else
    color green
    echo "'$NAME' test passed."
    color off
    let PASSED++
    let TOTAL++
  fi
done

echo "Total tests run: $TOTAL"
color green
echo "Tests passed: $PASSED"
color red
echo "Tests failed: $FAILED"
color off

rm -rf /tmp/$MODULE
rm /usr/local/bin/color
exit $FAILED
