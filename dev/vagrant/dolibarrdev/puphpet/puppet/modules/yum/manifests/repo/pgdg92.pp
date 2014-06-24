# = Class: yum::repo::pdgd92
#
# This class installs the postgresql 9.2 repo
#
class yum::repo::pgdg92 {

  yum::managed_yumrepo { 'pgdg92':
    descr          => 'PostgreSQL 9.2 $releasever - $basearch',
    baseurl        => 'http://yum.postgresql.org/9.2/redhat/rhel-$releasever-$basearch',
    enabled        => 1,
    gpgcheck       => 1,
    gpgkey         => 'file:///etc/pki/rpm-gpg/RPM-GPG-KEY-PGDG',
    gpgkey_source  => 'puppet:///modules/yum/rpm-gpg/RPM-GPG-KEY-PGDG',
    priority       => 20,
  }

}

