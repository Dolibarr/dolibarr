#!/bin/bash

export DEBIAN_FRONTEND=noninteractive

VAGRANT_CORE_FOLDER=$(cat '/.puphpet-stuff/vagrant-core-folder.txt')

OS=$(/bin/bash "${VAGRANT_CORE_FOLDER}/shell/os-detect.sh" ID)
CODENAME=$(/bin/bash "${VAGRANT_CORE_FOLDER}/shell/os-detect.sh" CODENAME)

# Directory in which r10k should manage its modules directory
PUPPET_DIR=/etc/puppet/

if [[ ! -d "${PUPPET_DIR}" ]]; then
    mkdir -p "${PUPPET_DIR}"
    echo "Created directory ${PUPPET_DIR}"
fi

cp "${VAGRANT_CORE_FOLDER}/puppet/Puppetfile" "${PUPPET_DIR}"
echo 'Copied Puppetfile'

echo 'Running update r10k'
cd "${PUPPET_DIR}" && r10k puppetfile install >/dev/null
echo 'Finished running update r10k'
