# = Class: yum::repo::pdgd91
#
# This class installs the postgresql 9.1 repo
#
class yum::repo::pgdg91 {

  yum::managed_yumrepo { 'pgdg91':
    descr          => 'PostgreSQL 9.1 $releasever - $basearch',
    baseurl        => 'http://yum.postgresql.org/9.1/redhat/rhel-$releasever-$basearch',
    enabled        => 1,
    gpgcheck       => 1,
    gpgkey         => 'file:///etc/pki/rpm-gpg/RPM-GPG-KEY-PGDG',
    gpgkey_source  => 'puppet:///modules/yum/rpm-gpg/RPM-GPG-KEY-PGDG',
    priority       => 20,
  }

}

