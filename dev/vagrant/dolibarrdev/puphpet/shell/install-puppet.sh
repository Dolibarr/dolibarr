#!/bin/bash

export DEBIAN_FRONTEND=noninteractive

VAGRANT_CORE_FOLDER=$(cat '/.puphpet-stuff/vagrant-core-folder.txt')

OS=$(/bin/bash "${VAGRANT_CORE_FOLDER}/shell/os-detect.sh" ID)
RELEASE=$(/bin/bash "${VAGRANT_CORE_FOLDER}/shell/os-detect.sh" RELEASE)
CODENAME=$(/bin/bash "${VAGRANT_CORE_FOLDER}/shell/os-detect.sh" CODENAME)

if [[ -f '/.puphpet-stuff/install-puppet' ]]; then
    exit 0
fi

if [ "${OS}" == 'debian' ] || [ "${OS}" == 'ubuntu' ]; then
    apt-get -y install augeas-tools libaugeas-dev
elif [[ "${OS}" == 'centos' ]]; then
    yum -y install augeas-devel
fi

echo 'Installing Puppet requirements'
/usr/bin/gem install haml hiera facter json ruby-augeas
echo 'Finished installing Puppet requirements'

echo 'Installing Puppet 3.4.3'
/usr/bin/gem install puppet --version 3.4.3

if [[ -f '/usr/bin/puppet' ]]; then
    mv /usr/bin/puppet /usr/bin/puppet-old
fi

ln -s /usr/local/rvm/wrappers/default/puppet /usr/bin/puppet
echo 'Finished installing Puppet 3.4.3'

touch '/.puphpet-stuff/install-puppet'
