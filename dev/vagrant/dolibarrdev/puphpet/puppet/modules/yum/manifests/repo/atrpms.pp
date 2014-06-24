# = Class: yum::repo::atrpms
#
# This class installs the atrpms repo
#
class yum::repo::atrpms {

  yum::managed_yumrepo { 'centos5-atrpms':
    descr          => 'CentOS $releasever - $basearch - ATrpms',
    baseurl        => 'http://dl.atrpms.net/el$releasever-$basearch/atrpms/stable',
    enabled        => 1,
    gpgcheck       => 1,
    gpgkey         => 'file:///etc/pki/rpm-gpg/RPM-GPG-KEY.atrpms',
    gpgkey_source  => 'puppet:///modules/yum/rpm-gpg/RPM-GPG-KEY.atrpms',
    priority       => 30,
  }

}
