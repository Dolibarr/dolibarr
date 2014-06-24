#!/bin/bash

export DEBIAN_FRONTEND=noninteractive

VAGRANT_CORE_FOLDER=$(cat '/.puphpet-stuff/vagrant-core-folder.txt')

shopt -s nullglob
files=("${VAGRANT_CORE_FOLDER}"/files/exec-once/*)

if [[ ! -f '/.puphpet-stuff/exec-once-ran' && (${#files[@]} -gt 0) ]]; then
    echo 'Running files in files/exec-once'
    find "${VAGRANT_CORE_FOLDER}/files/exec-once" -maxdepth 1 -not -path '*/\.*' -type f \( ! -iname "empty" \) -exec chmod +x '{}' \; -exec {} \;
    echo 'Finished running files in files/exec-once'
    echo 'To run again, delete file /.puphpet-stuff/exec-once-ran'
    touch /.puphpet-stuff/exec-once-ran
fi

echo 'Running files in files/exec-always'
find "${VAGRANT_CORE_FOLDER}/files/exec-always" -maxdepth 1 -not -path '*/\.*' -type f \( ! -iname "empty" \) -exec chmod +x '{}' \; -exec {} \;
echo 'Finished running files in files/exec-always'
