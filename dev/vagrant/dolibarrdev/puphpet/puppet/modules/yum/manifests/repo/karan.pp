# = Class: yum::repo::karan
#
# This class installs the karan repo
#
class yum::repo::karan {

  yum::managed_yumrepo { 'kbs-CentOS-Extras':
    descr          => 'CentOS.Karan.Org-EL$releasever - Stable',
    baseurl        => 'http://centos.karan.org/el$releasever/extras/stable/$basearch/RPMS/',
    enabled        => 1,
    gpgcheck       => 1,
    gpgkey         => 'file:///etc/pki/rpm-gpg/RPM-GPG-KEY-kbsingh',
    gpgkey_source  => 'puppet:///modules/yum/rpm-gpg/RPM-GPG-KEY-kbsingh',
    priority       => 20,
  }

  yum::managed_yumrepo { 'kbs-CentOS-Extras-Testing':
    descr    => 'CentOS.Karan.Org-EL$releasever - Testing',
    baseurl  => 'http://centos.karan.org/el$releasever/extras/testing/$basearch/RPMS/',
    enabled  => 0,
    gpgcheck => 1,
    gpgkey   => 'file:///etc/pki/rpm-gpg/RPM-GPG-KEY-kbsingh',
    priority => 20,
  }

  yum::managed_yumrepo { 'kbs-CentOS-Misc':
    descr    => 'CentOS.Karan.Org-EL$releasever - Stable',
    baseurl  => 'http://centos.karan.org/el$releasever/misc/stable/$basearch/RPMS/',
    enabled  => 1,
    gpgcheck => 1,
    gpgkey   => 'file:///etc/pki/rpm-gpg/RPM-GPG-KEY-kbsingh',
    priority => 20,
  }

  yum::managed_yumrepo { 'kbs-CentOS-Misc-Testing':
    descr    => 'CentOS.Karan.Org-EL$releasever - Testing',
    baseurl  => 'http://centos.karan.org/el$releasever/misc/testing/$basearch/RPMS/',
    enabled  => 0,
    gpgcheck => 1,
    gpgkey   => 'file:///etc/pki/rpm-gpg/RPM-GPG-KEY-kbsingh',
    priority => 20,
  }

}
