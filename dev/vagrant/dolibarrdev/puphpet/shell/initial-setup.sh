#!/bin/bash

export DEBIAN_FRONTEND=noninteractive

VAGRANT_CORE_FOLDER=$(echo "$1")

OS=$(/bin/bash "${VAGRANT_CORE_FOLDER}/shell/os-detect.sh" ID)
CODENAME=$(/bin/bash "${VAGRANT_CORE_FOLDER}/shell/os-detect.sh" CODENAME)

cat "${VAGRANT_CORE_FOLDER}/shell/self-promotion.txt"

if [[ ! -d '/.puphpet-stuff' ]]; then
    mkdir '/.puphpet-stuff'
    echo 'Created directory /.puphpet-stuff'
fi

touch '/.puphpet-stuff/vagrant-core-folder.txt'
echo "${VAGRANT_CORE_FOLDER}" > '/.puphpet-stuff/vagrant-core-folder.txt'

if [[ ! -f '/.puphpet-stuff/initial-setup-base-packages' ]]; then
    if [ "${OS}" == 'debian' ] || [ "${OS}" == 'ubuntu' ]; then
        echo 'Running initial-setup apt-get update'
        apt-get update >/dev/null
        echo 'Finished running initial-setup apt-get update'

        echo 'Installing git'
        apt-get -q -y install git-core >/dev/null
        echo 'Finished installing git'

        if [[ "${CODENAME}" == 'lucid' || "${CODENAME}" == 'precise' ]]; then
            echo 'Installing basic curl packages (Ubuntu only)'
            apt-get install -y libcurl3 libcurl4-gnutls-dev curl >/dev/null
            echo 'Finished installing basic curl packages (Ubuntu only)'
        fi

        echo 'Installing rubygems'
        apt-get install -y rubygems >/dev/null
        echo 'Finished installing rubygems'

        echo 'Installing base packages for r10k'
        apt-get install -y build-essential ruby-dev >/dev/null
        gem install json >/dev/null
        echo 'Finished installing base packages for r10k'

        if [ "${OS}" == 'ubuntu' ]; then
            echo 'Updating libgemplugin-ruby (Ubuntu only)'
            apt-get install -y libgemplugin-ruby >/dev/null
            echo 'Finished updating libgemplugin-ruby (Ubuntu only)'
        fi

        if [ "${CODENAME}" == 'lucid' ]; then
            echo 'Updating rubygems (Ubuntu Lucid only)'
            gem install rubygems-update >/dev/null 2>&1
            /var/lib/gems/1.8/bin/update_rubygems >/dev/null 2>&1
            echo 'Finished updating rubygems (Ubuntu Lucid only)'
        fi

        echo 'Installing r10k'
        gem install r10k >/dev/null 2>&1
        echo 'Finished installing r10k'

        touch '/.puphpet-stuff/initial-setup-base-packages'
    elif [[ "${OS}" == 'centos' ]]; then
        echo 'Running initial-setup yum update'
        perl -p -i -e 's@enabled=1@enabled=0@gi' /etc/yum/pluginconf.d/fastestmirror.conf
        perl -p -i -e 's@#baseurl=http://mirror.centos.org/centos/\$releasever/os/\$basearch/@baseurl=http://mirror.rackspace.com/CentOS//\$releasever/os/\$basearch/\nenabled=1@gi' /etc/yum.repos.d/CentOS-Base.repo
        perl -p -i -e 's@#baseurl=http://mirror.centos.org/centos/\$releasever/updates/\$basearch/@baseurl=http://mirror.rackspace.com/CentOS//\$releasever/updates/\$basearch/\nenabled=1@gi' /etc/yum.repos.d/CentOS-Base.repo
        perl -p -i -e 's@#baseurl=http://mirror.centos.org/centos/\$releasever/extras/\$basearch/@baseurl=http://mirror.rackspace.com/CentOS//\$releasever/extras/\$basearch/\nenabled=1@gi' /etc/yum.repos.d/CentOS-Base.repo

        yum -y --nogpgcheck install 'http://www.elrepo.org/elrepo-release-6-6.el6.elrepo.noarch.rpm' >/dev/null
        yum -y --nogpgcheck install 'https://dl.fedoraproject.org/pub/epel/6/x86_64/epel-release-6-8.noarch.rpm' >/dev/null
        yum -y install centos-release-SCL >/dev/null
        yum clean all >/dev/null
        yum -y check-update >/dev/null
        echo 'Finished running initial-setup yum update'

        echo 'Installing git'
        yum -y install git >/dev/null
        echo 'Finished installing git'

        echo 'Updating to Ruby 1.9.3'
        yum -y install centos-release-SCL >/dev/null 2>&1
        yum remove ruby >/dev/null 2>&1
        yum -y install ruby193 ruby193-ruby-irb ruby193-ruby-doc ruby193-libyaml rubygems >/dev/null 2>&1
        yum -y --nogpgcheck install 'https://yum.puppetlabs.com/el/6/dependencies/x86_64/ruby-rgen-0.6.5-2.el6.noarch.rpm' >/dev/null 2>&1
        gem update --system >/dev/null 2>&1
        gem install haml >/dev/null 2>&1

        yum -y --nogpgcheck install 'https://yum.puppetlabs.com/el/6/products/x86_64/hiera-1.3.2-1.el6.noarch.rpm' >/dev/null
        yum -y --nogpgcheck install 'https://yum.puppetlabs.com/el/6/products/x86_64/facter-1.7.5-1.el6.x86_64.rpm' >/dev/null
        yum -y --nogpgcheck install 'https://yum.puppetlabs.com/el/6/dependencies/x86_64/rubygem-json-1.5.5-1.el6.x86_64.rpm' >/dev/null
        yum -y --nogpgcheck install 'https://yum.puppetlabs.com/el/6/dependencies/x86_64/ruby-json-1.5.5-1.el6.x86_64.rpm' >/dev/null
        yum -y --nogpgcheck install 'https://yum.puppetlabs.com/el/6/dependencies/x86_64/ruby-shadow-2.2.0-2.el6.x86_64.rpm' >/dev/null
        yum -y --nogpgcheck install 'https://yum.puppetlabs.com/el/6/dependencies/x86_64/ruby-augeas-0.4.1-3.el6.x86_64.rpm' >/dev/null
        echo 'Finished updating to Ruby 1.9.3'

        echo 'Installing basic development tools (CentOS)'
        yum -y groupinstall 'Development Tools' >/dev/null
        echo 'Finished installing basic development tools (CentOS)'

        echo 'Installing r10k'
        gem install r10k >/dev/null 2>&1
        echo 'Finished installing r10k'

        touch '/.puphpet-stuff/initial-setup-base-packages'
    fi
fi
