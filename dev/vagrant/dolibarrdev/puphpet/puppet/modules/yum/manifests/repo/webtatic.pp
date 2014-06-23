# = Class: yum::repo::webtatic
#
# This class installs the webtatic repo
#
class yum::repo::webtatic {
  $osver = split($::operatingsystemrelease, '[.]')
  yum::managed_yumrepo { 'webtatic':
    descr          => 'Webtatic Repository $releasever - $basearch',
    mirrorlist     => $osver[0] ? {
      5 => 'http://repo.webtatic.com/yum/centos/5/$basearch/mirrorlist',
      6 => 'http://repo.webtatic.com/yum/el6/$basearch/mirrorlist',
    },
    enabled        => 1,
    gpgcheck       => 1,
    gpgkey         => 'file:///etc/pki/rpm-gpg/RPM-GPG-KEY-webtatic-andy',
    gpgkey_source  => 'puppet:///modules/yum/rpm-gpg/RPM-GPG-KEY-webtatic-andy',
    priority       => 1,
  }
}
