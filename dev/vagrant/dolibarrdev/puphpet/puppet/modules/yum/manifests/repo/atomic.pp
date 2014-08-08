# = Class: yum::repo::atomic
#
# This class installs the atomic repo
#
class yum::repo::atomic {
  yum::managed_yumrepo { 'atomic':
    descr          => 'CentOS / Red Hat Enterprise Linux $releasever - atomicrocketturtle.com',
    mirrorlist     => 'http://www.atomicorp.com/channels/mirrorlist/atomic/centos-$releasever-$basearch',
    enabled        => 1,
    gpgcheck       => 1,
    gpgkey         => 'file:///etc/pki/rpm-gpg/RPM-GPG-KEY.art',
    gpgkey_source  => 'puppet:///modules/yum/rpm-gpg/RPM-GPG-KEY.art',
    priority       => 1,
    exclude        => 'nmap-ncat',
  }
}
