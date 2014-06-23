# = Class: yum::repo::centos_testing
#
# Centos Testing
#
class yum::repo::centos_testing (
  $include_pkgs = '',
  $exclude_pkgs = undef
  ) {
  if $include_pkgs == '' {
    fail('Please configure $include_pkgs as we run the testing repo with highest repository')
  }

  yum::managed_yumrepo{'centos5-testing':
    descr       => 'CentOS-$releasever - Testing',
    baseurl     => 'http://dev.centos.org/centos/$releasever/testing/$basearch',
    enabled     => 1,
    gpgcheck    => 1,
    gpgkey      => 'http://dev.centos.org/centos/RPM-GPG-KEY-CentOS-testing',
    priority    => 1,
    includepkgs => $include_pkgs,
    exclude     => $exclude_pkgs,
  }
}
