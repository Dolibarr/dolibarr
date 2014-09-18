# = Class: yum::repo::newrelic
#
# This class installs the newrelic repo
#
class yum::repo::newrelic {

  yum::managed_yumrepo { 'newrelic':
    descr     => 'Newrelic official release packages',
    baseurl   => 'http://yum.newrelic.com/pub/newrelic/el5/$basearch/',
    enabled   => 1,
    gpgcheck  => 0,
    priority  => 1,
  }

}
