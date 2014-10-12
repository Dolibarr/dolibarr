#!/bin/bash

export DEBIAN_FRONTEND=noninteractive

VAGRANT_CORE_FOLDER=$(echo "$1")

OS=$(/bin/bash "${VAGRANT_CORE_FOLDER}/shell/os-detect.sh" ID)
CODENAME=$(/bin/bash "${VAGRANT_CORE_FOLDER}/shell/os-detect.sh" CODENAME)

cat "${VAGRANT_CORE_FOLDER}/shell/self-promotion.txt"
printf "\n"
echo ""

if [[ ! -d '/.puphpet-stuff' ]]; then
    mkdir '/.puphpet-stuff'
    echo 'Created directory /.puphpet-stuff'
fi

touch '/.puphpet-stuff/vagrant-core-folder.txt'
echo "${VAGRANT_CORE_FOLDER}" > '/.puphpet-stuff/vagrant-core-folder.txt'

if [[ -f '/.puphpet-stuff/initial-setup-base-packages' ]]; then
    exit 0
fi

if [ "${OS}" == 'debian' ] || [ "${OS}" == 'ubuntu' ]; then
    echo 'Running initial-setup apt-get update'
    apt-get update >/dev/null
    echo 'Finished running initial-setup apt-get update'

    echo 'Installing git'
    apt-get -y install git-core >/dev/null
    echo 'Finished installing git'

    if [[ "${CODENAME}" == 'lucid' || "${CODENAME}" == 'precise' ]]; then
        echo 'Installing basic curl packages'
        apt-get -y install libcurl3 libcurl4-gnutls-dev curl >/dev/null
        echo 'Finished installing basic curl packages'
    fi

    echo 'Installing build-essential package'
    apt-get -y install build-essential >/dev/null
    echo 'Finished installing build-essential packages'
elif [[ "${OS}" == 'centos' ]]; then
    echo 'Adding repos: elrep, epel, scl'
    perl -p -i -e 's@enabled=1@enabled=0@gi' /etc/yum/pluginconf.d/fastestmirror.conf
    perl -p -i -e 's@#baseurl=http://mirror.centos.org/centos/\$releasever/os/\$basearch/@baseurl=http://mirror.rackspace.com/CentOS//\$releasever/os/\$basearch/\nenabled=1@gi' /etc/yum.repos.d/CentOS-Base.repo
    perl -p -i -e 's@#baseurl=http://mirror.centos.org/centos/\$releasever/updates/\$basearch/@baseurl=http://mirror.rackspace.com/CentOS//\$releasever/updates/\$basearch/\nenabled=1@gi' /etc/yum.repos.d/CentOS-Base.repo
    perl -p -i -e 's@#baseurl=http://mirror.centos.org/centos/\$releasever/extras/\$basearch/@baseurl=http://mirror.rackspace.com/CentOS//\$releasever/extras/\$basearch/\nenabled=1@gi' /etc/yum.repos.d/CentOS-Base.repo

    yum -y --nogpgcheck install 'http://www.elrepo.org/elrepo-release-6-6.el6.elrepo.noarch.rpm' >/dev/null
    yum -y --nogpgcheck install 'https://dl.fedoraproject.org/pub/epel/6/x86_64/epel-release-6-8.noarch.rpm' >/dev/null
    yum -y install centos-release-SCL >/dev/null
    yum clean all >/dev/null
    yum -y check-update >/dev/null
    echo 'Finished adding repos: elrep, epel, scl'

    echo 'Installing git'
    yum -y install git >/dev/null
    echo 'Finished installing git'

    echo 'Installing Development Tools'
    yum -y groupinstall 'Development Tools' >/dev/null
    echo 'Finished installing Development Tools'
fi

touch '/.puphpet-stuff/initial-setup-base-packages'
