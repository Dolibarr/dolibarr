# = Class: yum::repo::foreman
#
# This class installs the foreman repo
#
class yum::repo::foreman {

  yum::managed_yumrepo { 'foreman':
    descr          => 'Foreman Repo',
    baseurl        => 'http://yum.theforeman.org/stable/',
    enabled        => 1,
    gpgcheck       => 0,
    failovermethod => 'priority',
    # gpgkey       => 'http://yum.theforeman.org/RPM-GPG-KEY-foreman',
    priority       => 1,
  }

}

