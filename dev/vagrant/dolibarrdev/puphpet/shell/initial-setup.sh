#!/bin/bash

export DEBIAN_FRONTEND=noninteractive

VAGRANT_CORE_FOLDER=$(echo "$1")

OS=$(/bin/bash "${VAGRANT_CORE_FOLDER}/shell/os-detect.sh" ID)
CODENAME=$(/bin/bash "${VAGRANT_CORE_FOLDER}/shell/os-detect.sh" CODENAME)

if [[ ! -d /.puphpet-stuff ]]; then
    mkdir /.puphpet-stuff

    echo "${VAGRANT_CORE_FOLDER}" > "/.puphpet-stuff/vagrant-core-folder.txt"

    cat "${VAGRANT_CORE_FOLDER}/shell/self-promotion.txt"
    echo "Created directory /.puphpet-stuff"
fi

if [[ ! -f /.puphpet-stuff/initial-setup-repo-update ]]; then
    if [ "${OS}" == 'debian' ] || [ "${OS}" == 'ubuntu' ]; then
        echo "Running initial-setup apt-get update"
        apt-get update >/dev/null
        touch /.puphpet-stuff/initial-setup-repo-update
        echo "Finished running initial-setup apt-get update"
    elif [[ "${OS}" == 'centos' ]]; then
        echo "Running initial-setup yum update"
        yum -y --nogpgcheck install "http://www.elrepo.org/elrepo-release-6-6.el6.elrepo.noarch.rpm" >/dev/null
        yum -y --nogpgcheck install "https://dl.fedoraproject.org/pub/epel/6/x86_64/epel-release-6-8.noarch.rpm" >/dev/null
        yum -y install centos-release-SCL yum-plugin-fastestmirror >/dev/null
        yum -y check-update >/dev/null
        echo "Finished running initial-setup yum update"

        echo "Updating to Ruby 1.9.3"
        yum -y install centos-release-SCL >/dev/null
        yum remove ruby >/dev/null
        yum -y install ruby193 ruby193-ruby-irb ruby193-ruby-doc ruby193-libyaml rubygems >/dev/null
        yum -y --nogpgcheck install "https://yum.puppetlabs.com/el/6/dependencies/x86_64/ruby-rgen-0.6.5-2.el6.noarch.rpm" >/dev/null
        gem update --system >/dev/null
        gem install haml >/dev/null

        yum -y --nogpgcheck install "https://yum.puppetlabs.com/el/6/products/x86_64/hiera-1.3.2-1.el6.noarch.rpm" >/dev/null
        yum -y --nogpgcheck install "https://yum.puppetlabs.com/el/6/products/x86_64/facter-1.7.5-1.el6.x86_64.rpm" >/dev/null
        yum -y --nogpgcheck install "https://yum.puppetlabs.com/el/6/dependencies/x86_64/rubygem-json-1.5.5-1.el6.x86_64.rpm" >/dev/null
        yum -y --nogpgcheck install "https://yum.puppetlabs.com/el/6/dependencies/x86_64/ruby-json-1.5.5-1.el6.x86_64.rpm" >/dev/null
        yum -y --nogpgcheck install "https://yum.puppetlabs.com/el/6/dependencies/x86_64/ruby-shadow-2.2.0-2.el6.x86_64.rpm" >/dev/null
        yum -y --nogpgcheck install "https://yum.puppetlabs.com/el/6/dependencies/x86_64/ruby-augeas-0.4.1-3.el6.x86_64.rpm" >/dev/null
        echo "Finished updating to Ruby 1.9.3"

        echo "Installing basic development tools (CentOS)"
        yum -y groupinstall "Development Tools" >/dev/null
        echo "Finished installing basic development tools (CentOS)"
        touch /.puphpet-stuff/initial-setup-repo-update
    fi
fi

if [[ "${OS}" == 'ubuntu' && ("${CODENAME}" == 'lucid' || "${CODENAME}" == 'precise') && ! -f /.puphpet-stuff/ubuntu-required-libraries ]]; then
    echo 'Installing basic curl packages (Ubuntu only)'
    apt-get install -y libcurl3 libcurl4-gnutls-dev curl >/dev/null
    echo 'Finished installing basic curl packages (Ubuntu only)'

    touch /.puphpet-stuff/ubuntu-required-libraries
fi
