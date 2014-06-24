# = Class: yum::repo::rpmforge
#
# This class installs the rpmforce repo
#
class yum::repo::rpmforge {

  yum::managed_yumrepo { 'rpmforge-rhel5':
    descr          => 'RPMForge RHEL5 packages',
    baseurl        => 'http://wftp.tu-chemnitz.de/pub/linux/dag/redhat/el$releasever/en/$basearch/dag',
    enabled        => 1,
    gpgcheck       => 1,
    gpgkey         => 'file:///etc/pki/rpm-gpg/RPM-GPG-KEY-rpmforge-dag',
    gpgkey_source  => 'puppet:///modules/yum/rpm-gpg/RPM-GPG-KEY-rpmforge-dag',
    priority       => 30,
  }

}
