# = Class: yum::repo::repoforgeextras
#
# This class installs the repoforge extras repo
#
class yum::repo::repoforgeextras {

  yum::managed_yumrepo { 'repoforgeextras':
    descr    => 'RepoForge extra packages',
    baseurl  => 'http://apt.sw.be/redhat/el$releasever/en/$basearch/extras',
    enabled  => 1,
    gpgcheck => 1,
    gpgkey   => 'file:///etc/pki/rpm-gpg/RPM-GPG-KEY-rpmforge-dag',
    priority => 1,
    exclude  => 'perl-IO-Compress-* perl-DBD-MySQL',
  }

}
