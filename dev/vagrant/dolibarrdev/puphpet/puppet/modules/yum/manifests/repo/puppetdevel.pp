# = Class: yum::repo::puppetdevel
#
# This class installs the puppetdevel repo
#
class yum::repo::puppetdevel {

  yum::managed_yumrepo { 'puppetlabs_devel':
    descr          => 'Puppet Labs Packages - Devel',
    baseurl        => 'http://yum.puppetlabs.com/el/$releasever/devel/$basearch',
    enabled        => 1,
    gpgcheck       => 1,
    failovermethod => 'priority',
    gpgkey         => 'http://yum.puppetlabs.com/RPM-GPG-KEY-puppetlabs',
    priority       => 15,
  }

  yum::managed_yumrepo { 'puppetlabs_dependencies':
    descr          => 'Puppet Labs Packages - Dependencies',
    baseurl        => 'http://yum.puppetlabs.com/el/$releasever/dependencies/$basearch',
    enabled        => 1,
    gpgcheck       => 1,
    failovermethod => 'priority',
    gpgkey         => 'http://yum.puppetlabs.com/RPM-GPG-KEY-puppetlabs',
    priority       => 15,
  }

}
