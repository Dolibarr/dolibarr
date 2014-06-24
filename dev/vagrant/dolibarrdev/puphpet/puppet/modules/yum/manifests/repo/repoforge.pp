# = Class: yum::repo::repoforge
#
# This class installs the repoforge repo
#
class yum::repo::repoforge {

  yum::managed_yumrepo { 'repoforge':
    descr          => 'RepoForge packages',
    baseurl        => 'http://apt.sw.be/redhat/el$releasever/en/$basearch/rpmforge',
    enabled        => 1,
    gpgcheck       => 1,
    gpgkey         => 'file:///etc/pki/rpm-gpg/RPM-GPG-KEY-rpmforge-dag',
    gpgkey_source  => 'puppet:///modules/yum/rpm-gpg/RPM-GPG-KEY-rpmforge-dag',
    priority       => 1,
    exclude        => 'nagios-*',
  }

}
