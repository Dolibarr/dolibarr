# = Class: yum::repo::jpackage5
#
# This class installs the jpackage5 repo
#
class yum::repo::jpackage5 {

  include yum

  yum::managed_yumrepo { 'jpackage-generic-5.0':
    descr          => 'JPackage (free), generic',
    mirrorlist     => 'http://www.jpackage.org/mirrorlist.php?dist=generic&type=free&release=5.0',
    failovermethod => 'priority',
    gpgcheck       => 1,
    gpgkey         => 'http://www.jpackage.org/jpackage.asc',
    enabled        => 1,
    priority       => 10,
  }

  yum::managed_yumrepo { 'jpackage-generic-5.0-updates':
    descr          => 'JPackage (free), generic updates',
    mirrorlist     => 'http://www.jpackage.org/mirrorlist.php?dist=generic&type=free&release=5.0-updates',
    failovermethod => 'priority',
    gpgcheck       => 1,
    gpgkey         => 'http://www.jpackage.org/jpackage.asc',
    enabled        => 1,
    priority       => 10,
  }

  yum::managed_yumrepo { 'jpackage-rhel':
    descr          => 'JPackage (free) for Red Hat Enterprise Linux $releasever',
    mirrorlist     => 'http://www.jpackage.org/mirrorlist.php?dist=redhat-el-$releasever&type=free&release=5.0',
    failovermethod => 'priority',
    gpgcheck       => 1,
    gpgkey         => 'http://www.jpackage.org/jpackage.asc',
    enabled        => 1,
    priority       => 10,
  }

  yum::managed_yumrepo { 'jpackage-generic-5.0-devel':
    descr          => 'JPackage (free), generic',
    baseurl        => 'http://mirrors.dotsrc.org/jpackage/5.0/generic/devel',
    failovermethod => 'priority',
    gpgcheck       => 1,
    gpgkey         => 'http://www.jpackage.org/jpackage.asc',
    enabled        => 0,
    priority       => 10,
  }

}
