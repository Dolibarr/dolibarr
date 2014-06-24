# = Class: yum::repo::pdgd93
#
# This class installs the postgresql 9.3 repo
#
class yum::repo::pgdg93 {

  yum::managed_yumrepo { 'pgdg93':
    descr          => 'PostgreSQL 9.3 $releasever - $basearch',
    baseurl        => 'http://yum.postgresql.org/9.3/redhat/rhel-$releasever-$basearch',
    enabled        => 1,
    gpgcheck       => 1,
    gpgkey         => 'file:///etc/pki/rpm-gpg/RPM-GPG-KEY-PGDG',
    gpgkey_source  => 'puppet:///modules/yum/rpm-gpg/RPM-GPG-KEY-PGDG',
    priority       => 20,
  }

}

