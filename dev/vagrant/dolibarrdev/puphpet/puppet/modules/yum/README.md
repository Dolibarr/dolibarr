# Puppet module: yum

This is a Puppet module that manages Yum repositories for Centos RedHat and Scientific Linux

Made by Alessandro Franceschi / Lab42

Inspired by the Yum Immerda module: https://git.puppet.immerda.ch

Official site: http://www.example42.com

Official git repository: http://github.com/example42/puppet-yum

Released under the terms of Apache 2 License.

This module requires functions provided by the Example42 Puppi module.

## USAGE 

* Just leave the default options: Automatic detection of Operating System (RedHat, Centos, Scientific supported) Epel repo installation, keeping of local yum files, automatic updates disabled.

        class { 'yum':
        }

* Enable automatic updates via cron (updatesd is supported only on 5)

        class { 'yum':
          update => 'cron',
        }


* Purge local /etc/yum.repos.d/ and enforce its contents only via a custom source

        class { 'yum':
          source_repo_dir => 'puppet:///modules/example42/yum/conf/',
          clean_repos     => true,
        }

* Enable EPEL and PuppetLabs repos

        class { 'yum':
          extrarepo => [ 'epel' , 'puppetlabs' ],
        }


* Do not include any extra repo (By default EPEL is added)

        class { 'yum':
          extrarepo => '' ,
        }

* Automatically copy in /etc/pki/rpm-gpg  all the rpm-gpg keys known by the yum module (this was the "old" and intrusive behaviour, now each rpm-gpg key may be individually provided by the yum::manages_repos' gpgkey_source parameter)

        class { 'yum':
          install_all_keys => true ,
        }

* Include a selected extra repo

        include yum::repo::puppetlabs


## USAGE - Overrides and Customizations
* Enable auditing without without making changes on existing yum configuration files

        class { 'yum':
          audit_only => true
        }


* Use custom sources for main config file

        class { 'yum':
          source => [ "puppet:///modules/lab42/yum/yum.conf-${hostname}" , "puppet:///modules/lab42/yum/yum.conf" ],
        }


* Use custom source directory for the whole configuration dir

        class { 'yum':
          source_dir       => 'puppet:///modules/lab42/yum/conf/',
          source_dir_purge => false, # Set to true to purge any existing file not present in $source_dir
        }

* Use custom template for main config file. Note that template and source arguments are alternative.

        class { 'yum':
          template => 'example42/yum/yum.conf.erb',
        }

* Automatically include a custom subclass

        class { 'yum':
          my_class => 'yum::example42',
        }


## USAGE - Example42 extensions management
* Activate puppi (recommended, but disabled by default)

        class { 'yum':
          puppi    => true,
        }

* Activate puppi and use a custom puppi_helper template (to be provided separately with a puppi::helper define ) to customize the output of puppi commands

        class { 'yum':
          puppi        => true,
          puppi_helper => 'myhelper',
        }


## OPERATING SYSTEMS SUPPORT

REDHAT 6 - Full

REDHAT 5 - Full

REDHAT 4 - Partial

CENTOS 6 - Full

CENTOS 5 - Full

CENTOS 4 - Partial

SCIENTIFIC 6 - Full

SCIENTIFIC 5 - Full

AMAZON LINUX 3 (Sigh) - Partial

[![Build Status](https://travis-ci.org/example42/puppet-yum.png?branch=master)](https://travis-ci.org/example42/puppet-yum)
