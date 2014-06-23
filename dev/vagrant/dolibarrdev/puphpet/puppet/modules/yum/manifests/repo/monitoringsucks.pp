# = Class: yum::repo::monitoringsucks
#
# This class installs the monitoringsucks repo
#
class yum::repo::monitoringsucks {

  yum::managed_yumrepo { 'monitoringsucks':
    descr          => 'MonitoringSuck at Inuits',
    baseurl        => 'http://pulp.inuits.eu/pulp/repos/monitoring',
    enabled        => 1,
    gpgcheck       => 0,
    failovermethod => 'priority',
    priority       => 99,
  }

}
