#!/bin/bash

VAGRANT_CORE_FOLDER=$(cat '/.puphpet-stuff/vagrant-core-folder.txt')

if [[ -f '/.puphpet-stuff/displayed-important-notices' ]]; then
    exit 0
fi

cat "${VAGRANT_CORE_FOLDER}/shell/important-notices.txt"

touch '/.puphpet-stuff/displayed-important-notices'
