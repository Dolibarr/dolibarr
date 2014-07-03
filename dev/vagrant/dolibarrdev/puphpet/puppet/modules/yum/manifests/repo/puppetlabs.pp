# = Class: yum::repo::puppetlabs
#
# This class installs the puppetlabs repo
#
class yum::repo::puppetlabs {
  $osver = split($::operatingsystemrelease, '[.]')
  $release = $::operatingsystem ? {
    /(?i:Centos|RedHat|Scientific)/ => $osver[0],
    default                         => '6',
  }

  yum::managed_yumrepo { 'puppetlabs':
    descr          => 'Puppet Labs Packages',
    baseurl        => "http://yum.puppetlabs.com/el/${release}/products/\$basearch",
    enabled        => 1,
    gpgcheck       => 1,
    failovermethod => 'priority',
    gpgkey         => 'http://yum.puppetlabs.com/RPM-GPG-KEY-puppetlabs',
    priority       => 1,
  }

  # The dependencies repo has the same priority as base,
  # because it needs to override base packages.
  # E.g. puppet-3.0 requires Ruby => 1.8.7, but EL5 ships with 1.8.5.
  #
  yum::managed_yumrepo { 'puppetlabs_dependencies':
    descr          => 'Puppet Labs Packages',
    baseurl        => "http://yum.puppetlabs.com/el/${release}/dependencies/\$basearch",
    enabled        => 1,
    gpgcheck       => 1,
    failovermethod => 'priority',
    gpgkey         => 'http://yum.puppetlabs.com/RPM-GPG-KEY-puppetlabs',
    priority       => 1,
  }

}
