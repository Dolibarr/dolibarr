# = Class: yum::repo::elrepo
#
# This class installs the ELRepo repository
#
# == Parameters:
#
# [*mirror_url*]
#   A clean URL to a mirror of `http://elrepo.org/linux/`.
#   The paramater is interpolated with the known directory structure to
#   create a the final baseurl parameter for each yumrepo so it must be
#   "clean", i.e., without a query string like `?key1=valA&key2=valB`.
#   Additionally, it may not contain a trailing slash.
#   Example: `http://elrepo.org/linux/`
#   Default: `undef`
#
class yum::repo::elrepo (
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
    'RedHat','CentOS','Scientific': {
      $release = "el${osver[0]}"
    }
    default: {
      fail("${title}: Operating system '${::operatingsystem}' is not currently supported")
    }
  }

  $baseurl_elrepo = $mirror_url ? {
    undef   => undef,
    default => "${mirror_url}/elrepo/${release}/\$basearch",
  }

  $baseurl_elrepo_testing = $mirror_url ? {
    undef   => undef,
    default => "${mirror_url}/testing/${release}/\$basearch",
  }

  $baseurl_elrepo_kernel = $mirror_url ? {
    undef   => undef,
    default => "${mirror_url}/kernel/${release}/\$basearch",
  }

  $baseurl_elrepo_extras = $mirror_url ? {
    undef   => undef,
    default => "${mirror_url}/extras/${release}/\$basearch",
  }

  yum::managed_yumrepo { 'elrepo':
    descr          => "ELRepo.org Community Enterprise Linux Repository - ${release}",
    baseurl        => $baseurl_elrepo,
    mirrorlist     => "http://elrepo.org/mirrors-elrepo.${release}",
    enabled        => 1,
    gpgcheck       => 1,
    gpgkey         => 'file:///etc/pki/rpm-gpg/RPM-GPG-KEY-elrepo.org',
    gpgkey_source  => 'puppet:///modules/yum/rpm-gpg/RPM-GPG-KEY-elrepo.org',
    failovermethod => 'priority',
    priority       => 17,
  }

  yum::managed_yumrepo { 'elrepo-testing':
    descr          => "ELRepo.org Community Enterprise Linux Testing Repository - ${release}",
    baseurl        => $baseurl_elrepo_testing,
    mirrorlist     => "http://elrepo.org/mirrors-elrepo-testing.${release}",
    enabled        => 0,
    gpgcheck       => 1,
    gpgkey         => 'file:///etc/pki/rpm-gpg/RPM-GPG-KEY-elrepo.org',
    failovermethod => 'priority',
    priority       => 17,
  }

  yum::managed_yumrepo { 'elrepo-kernel':
    descr          => "ELRepo.org Community Enterprise Linux Kernel Repository - ${release}",
    baseurl        => $baseurl_elrepo_kernel,
    mirrorlist     => "http://elrepo.org/mirrors-elrepo-kernel.${release}",
    enabled        => 0,
    gpgcheck       => 1,
    gpgkey         => 'file:///etc/pki/rpm-gpg/RPM-GPG-KEY-elrepo.org',
    failovermethod => 'priority',
    priority       => 17,
  }

  yum::managed_yumrepo { 'elrepo-extras':
    descr          => "ELRepo.org Community Enterprise Linux Extras Repository - ${release}",
    baseurl        => $baseurl_elrepo_extras,
    mirrorlist     => "http://elrepo.org/mirrors-elrepo-extras.${release}",
    enabled        => 0,
    gpgcheck       => 1,
    gpgkey         => 'file:///etc/pki/rpm-gpg/RPM-GPG-KEY-elrepo.org',
    failovermethod => 'priority',
    priority       => 17,
  }

}
