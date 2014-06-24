#!/bin/bash

export DEBIAN_FRONTEND=noninteractive

VAGRANT_CORE_FOLDER=$(cat '/.puphpet-stuff/vagrant-core-folder.txt')

OS=$(/bin/bash "${VAGRANT_CORE_FOLDER}/shell/os-detect.sh" ID)
RELEASE=$(/bin/bash "${VAGRANT_CORE_FOLDER}/shell/os-detect.sh" RELEASE)
CODENAME=$(/bin/bash "${VAGRANT_CORE_FOLDER}/shell/os-detect.sh" CODENAME)

if [[ ! -f '/.puphpet-stuff/update-puppet' ]]; then
    if [ "${OS}" == 'debian' ] || [ "${OS}" == 'ubuntu' ]; then
        echo "Downloading http://apt.puppetlabs.com/puppetlabs-release-${CODENAME}.deb"
        wget --quiet --tries=5 --connect-timeout=10 -O "/.puphpet-stuff/puppetlabs-release-${CODENAME}.deb" "http://apt.puppetlabs.com/puppetlabs-release-${CODENAME}.deb"
        echo "Finished downloading http://apt.puppetlabs.com/puppetlabs-release-${CODENAME}.deb"

        dpkg -i "/.puphpet-stuff/puppetlabs-release-${CODENAME}.deb" >/dev/null

        echo 'Running update-puppet apt-get update'
        apt-get update >/dev/null
        echo 'Finished running update-puppet apt-get update'

        echo 'Updating Puppet to version 3.4.x'
        apt-get install -y puppet-common=3.4.* puppet=3.4.* >/dev/null
        apt-mark hold puppet puppet-common >/dev/null
        PUPPET_VERSION=$(puppet help | grep 'Puppet v')
        echo "Finished updating puppet to latest version: ${PUPPET_VERSION}"

        touch '/.puphpet-stuff/update-puppet'
    elif [ "${OS}" == 'centos' ]; then
        echo "Downloading http://yum.puppetlabs.com/el/${RELEASE}/products/x86_64/puppet-3.4.3-1.el6.noarch.rpm"
        yum -y --nogpgcheck install "http://yum.puppetlabs.com/el/${RELEASE}/products/x86_64/puppet-3.4.3-1.el6.noarch.rpm" >/dev/null
        echo "Finished downloading http://yum.puppetlabs.com/el/${RELEASE}/products/x86_64/puppet-3.4.3-1.el6.noarch.rpm"

        echo 'Installing/Updating Puppet to version 3.4.x'
        yum -y install yum-versionlock puppet >/dev/null
        yum versionlock puppet
        PUPPET_VERSION=$(puppet help | grep 'Puppet v')
        echo "Finished installing/updating puppet to version: ${PUPPET_VERSION}"

        touch '/.puphpet-stuff/update-puppet'
    fi
fi
