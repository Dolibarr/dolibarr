#!/bin/bash

export DEBIAN_FRONTEND=noninteractive

VAGRANT_CORE_FOLDER=$(cat "/.puphpet-stuff/vagrant-core-folder.txt")

OS=$(/bin/bash "${VAGRANT_CORE_FOLDER}/shell/os-detect.sh" ID)
CODENAME=$(/bin/bash "${VAGRANT_CORE_FOLDER}/shell/os-detect.sh" CODENAME)

# Directory in which r10k should manage its modules directory
PUPPET_DIR=/etc/puppet/

$(which git > /dev/null 2>&1)
FOUND_GIT=$?

if [ "${FOUND_GIT}" -ne '0' ] && [ ! -f /.puphpet-stuff/r10k-installed ]; then
    $(which apt-get > /dev/null 2>&1)
    FOUND_APT=$?
    $(which yum > /dev/null 2>&1)
    FOUND_YUM=$?

    echo 'Installing git'

    if [ "${FOUND_YUM}" -eq '0' ]; then
        yum -q -y makecache
        yum -q -y install git
    else
        apt-get -q -y install git-core >/dev/null
    fi

    echo 'Finished installing git'
fi

if [[ ! -d "${PUPPET_DIR}" ]]; then
    mkdir -p "${PUPPET_DIR}"
    echo "Created directory ${PUPPET_DIR}"
fi

cp "${VAGRANT_CORE_FOLDER}/puppet/Puppetfile" "${PUPPET_DIR}"
echo "Copied Puppetfile"

if [ "${OS}" == 'debian' ] || [ "${OS}" == 'ubuntu' ]; then
    if [[ ! -f /.puphpet-stuff/r10k-base-packages ]]; then
        echo 'Installing base packages for r10k'
        apt-get install -y build-essential ruby-dev >/dev/null
        gem install json >/dev/null
        echo 'Finished installing base packages for r10k'

        touch /.puphpet-stuff/r10k-base-packages
    fi
fi

if [ "${OS}" == 'ubuntu' ]; then
    if [[ ! -f /.puphpet-stuff/r10k-libgemplugin-ruby ]]; then
        echo 'Updating libgemplugin-ruby (Ubuntu only)'
        apt-get install -y libgemplugin-ruby >/dev/null
        echo 'Finished updating libgemplugin-ruby (Ubuntu only)'

        touch /.puphpet-stuff/r10k-libgemplugin-ruby
    fi

    if [ "${CODENAME}" == 'lucid' ] && [ ! -f /.puphpet-stuff/r10k-rubygems-update ]; then
        echo 'Updating rubygems (Ubuntu Lucid only)'
        echo 'Ignore all "conflicting chdir" errors!'
        gem install rubygems-update >/dev/null
        /var/lib/gems/1.8/bin/update_rubygems >/dev/null
        echo 'Finished updating rubygems (Ubuntu Lucid only)'

        touch /.puphpet-stuff/r10k-rubygems-update
    fi
fi

if [[ ! -f /.puphpet-stuff/r10k-puppet-installed ]]; then
    echo 'Installing r10k'
    gem install r10k >/dev/null
    echo 'Finished installing r10k'

    echo 'Running initial r10k'
    cd "${PUPPET_DIR}" && r10k puppetfile install >/dev/null
    echo 'Finished running initial r10k'

    touch /.puphpet-stuff/r10k-puppet-installed
else
    echo 'Running update r10k'
    cd "${PUPPET_DIR}" && r10k puppetfile install >/dev/null
    echo 'Finished running update r10k'
fi
