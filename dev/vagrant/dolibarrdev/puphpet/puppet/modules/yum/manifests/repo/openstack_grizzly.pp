# = Class: yum::repo::openstack_grizzly
#
# This class installs the EPEL-6 repo for OpenStack Grizzly
#
class yum::repo::openstack_grizzly {

  yum::managed_yumrepo { 'epel-openstack-grizzly':
    descr          => 'OpenStack Grizzly Repository for EPEL 6',
    baseurl        => 'http://repos.fedorapeople.org/repos/openstack/openstack-grizzly/epel-6',
    enabled        => 1,
    gpgcheck       => 0,
    failovermethod => 'priority',
    priority       => 1,
  }
}
