# = Class: yum::repo::integ_ganeti
#
# This class installs the Integ Ganeti Yum repo
#
# == Parameters:
#
# [*mirror_url*]
#   A clean URL to a mirror of `http://jfut.integ.jp/linux/ganeti/`.
#   The paramater is interpolated with the known directory structure to
#   create a the final baseurl parameter for each yumrepo so it must be
#   "clean", i.e., without a query string like `?key1=valA&key2=valB`.
#   Additionally, it may not contain a trailing slash.
#   Example: `http://mirror.example.com/pub/rpm/ganeti`
#   Default: `undef`
#
class yum::repo::integ_ganeti (
  $mirror_url = undef,
) {

  if $mirror_url {
    validate_re(
      $mirror_url,
      '^(?:https?|ftp):\/\/[\da-zA-Z-][\da-zA-Z\.-]*\.[a-zA-Z]{2,6}\.?(?:\/[\w~-]*)*$',
      '$mirror must be a Clean URL with no query-string, a fully-qualified hostname and no trailing slash.'
    )
  }

  # Workaround for Facter < 1.7.0
  $osver = split($::operatingsystemrelease, '[.]')

  case $::operatingsystem {
    'Fedora': {
      $release = 'fedora'
    }
    'RedHat','CentOS','Scientific': {
      $release = $osver[0]
    }
    default: {
      fail("${title}: Operating system '${::operatingsystem}' is not currently supported")
    }
  }

  $baseurl_integ_ganeti = $mirror_url ? {
    undef   => "http://jfut.integ.jp/linux/ganeti/${release}/\$basearch",
    default => "${mirror_url}/${release}/\$basearch",
  }

  $baseurl_integ_ganeti_source = $mirror_url ? {
    undef   => "http://jfut.integ.jp/linux/ganeti/${release}/SRPMS",
    default => "${mirror_url}/${release}/SRPMS",
  }

  yum::managed_yumrepo { 'integ-ganeti':
    descr          => "Integ Ganeti Packages ${osver[0]} - \$basearch",
    baseurl        => $baseurl_integ_ganeti,
    enabled        => 1,
    gpgcheck       => 0,
    priority       => 15,
  }

  yum::managed_yumrepo { 'integ-ganeti-source':
    descr          => "Integ Ganeti Packages ${osver[0]} - Source",
    baseurl        => $baseurl_integ_ganeti_source,
    enabled        => 0,
    gpgcheck       => 0,
    priority       => 15,
  }

}
