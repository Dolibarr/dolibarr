# = Class: yum::repo::10gen
#
# This class installs the 10gen repo for MongoDB
#
class yum::repo::10gen {
  yum::managed_yumrepo { '10gen':
    descr       => '10gen Repository',
    baseurl     => "http://downloads-distro.mongodb.org/repo/redhat/os/${::architecture}",
    enabled     => 1,
    gpgcheck    => 0,
  }
}
