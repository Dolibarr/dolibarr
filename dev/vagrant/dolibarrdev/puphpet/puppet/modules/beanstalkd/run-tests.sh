#!/usr/bin/env sh

bundle="bundle"
gotbundle=0
for i in $(echo "$PATH" | tr ":" " ")
 do
        if [ -e $i/$bundle ]
         then
                gotbundle=1
                break
        fi
done
if [ $gotbundle = 0 ]
 then
        echo "ERROR: please install 'bundler' for ruby from http://gembundler.com/ and make sure '$bundle' is in your path"
        exit 1
fi

$bundle install || exit $?
$bundle exec rake || exit $?
