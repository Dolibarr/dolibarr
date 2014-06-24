# = Class: yum::repo::jpackage6
#
# This class installs the jpackage6 repo
#
class yum::repo::jpackage6 {

  yum::managed_yumrepo { 'jpackage':
    descr          => 'JPackage 6 generic',
    mirrorlist     => 'http://www.jpackage.org/mirrorlist.php?dist=generic&type=free&release=6.0',
    failovermethod => 'priority',
    gpgcheck       => 1,
    gpgkey         => 'http://www.jpackage.org/jpackage.asc',
    enabled        => 1,
    priority       => 1,
  }

}
