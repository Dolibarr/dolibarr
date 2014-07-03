# = Class: yum::repo::remi_php55
#
# This class installs the remi-php55 repo
#
class yum::repo::remi_php55 {
  yum::managed_yumrepo { 'remi-php55':
    descr          => 'Les RPM de remi pour Enterpise Linux $releasever - $basearch - PHP 5.5',
    mirrorlist     => 'http://rpms.famillecollet.com/enterprise/$releasever/php55/mirror',
    enabled        => 1,
    gpgcheck       => 1,
    gpgkey         => 'file:///etc/pki/rpm-gpg/RPM-GPG-KEY-remi',
    gpgkey_source  => 'puppet:///modules/yum/rpm-gpg/RPM-GPG-KEY-remi',
    priority       => 1,
  }
}
