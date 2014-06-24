# = Class: yum::repo::epel
#
# This class installs the epel repo
#
# == Parameters:
#
# [*mirror_url*]
#   A clean URL to a mirror of `http://dl.fedoraproject.org/pub/epel/`.
#   The paramater is interpolated with the known directory structure to
#   create a the final baseurl parameter for each yumrepo so it must be
#   "clean", i.e., without a query string like `?key1=valA&key2=valB`.
#   Additionally, it may not contain a trailing slash.
#   Example: `http://mirror.example.com/pub/rpm/epel`
#   Default: `undef`
#
class yum::repo::epel (
  $mirror_url = undef,
) {

  if $mirror_url {
    validate_re(
      $mirror_url,
      '^(?:https?|ftp):\/\/[\da-zA-Z-][\da-zA-Z\.-]*\.[a-zA-Z]{2,6}\.?(?:\/[\w~-]*)*$',
      '$mirror must be a Clean URL with no query-string, a fully-qualified hostname and no trailing slash.'
    )
  }

  if $::operatingsystem == 'Amazon' {
    $osver = [ '6' ]
  } else {
    $osver = split($::operatingsystemrelease, '[.]')
  }

  $baseurl_epel = $mirror_url ? {
    undef   => undef,
    default => "${mirror_url}/${osver[0]}/\$basearch/",
  }

  $baseurl_epel_debuginfo = $mirror_url ? {
    undef   => undef,
    default => "${mirror_url}/${osver[0]}/\$basearch/debug",
  }

  $baseurl_epel_source = $mirror_url ? {
    undef   => undef,
    default => "${mirror_url}/${osver[0]}/SRPMS/",
  }

  $baseurl_epel_testing = $mirror_url ? {
    undef   => undef,
    default => "${mirror_url}/testing/${osver[0]}/\$basearch/",
  }

  $baseurl_epel_testing_debuginfo = $mirror_url ? {
    undef   => undef,
    default => "${mirror_url}/testing/${osver[0]}/\$basearch/debug",
  }

  $baseurl_epel_testing_source = $mirror_url ? {
    undef   => undef,
    default => "${mirror_url}/testing/${osver[0]}/SRPMS/",
  }

  yum::managed_yumrepo { 'epel':
    descr          => "Extra Packages for Enterprise Linux ${osver[0]} - \$basearch",
    baseurl        => $baseurl_epel,
    mirrorlist     => "http://mirrors.fedoraproject.org/mirrorlist?repo=epel-${osver[0]}&arch=\$basearch",
    enabled        => 1,
    gpgcheck       => 1,
    failovermethod => 'priority',
    gpgkey         => "file:///etc/pki/rpm-gpg/RPM-GPG-KEY-EPEL-${osver[0]}",
    gpgkey_source  => "puppet:///modules/yum/rpm-gpg/RPM-GPG-KEY-EPEL-${osver[0]}",
    priority       => 16,
  }

  yum::managed_yumrepo { 'epel-debuginfo':
    descr          => "Extra Packages for Enterprise Linux ${osver[0]} - \$basearch - Debug",
    baseurl        => $baseurl_epel_debuginfo,
    mirrorlist     => "http://mirrors.fedoraproject.org/mirrorlist?repo=epel-${osver[0]}&arch=\$basearch",
    enabled        => 0,
    gpgcheck       => 1,
    failovermethod => 'priority',
    gpgkey         => "file:///etc/pki/rpm-gpg/RPM-GPG-KEY-EPEL-${osver[0]}",
    priority       => 16,
  }

  yum::managed_yumrepo { 'epel-source':
    descr          => "Extra Packages for Enterprise Linux ${osver[0]} - \$basearch - Source",
    baseurl        => $baseurl_epel_source,
    mirrorlist     => "http://mirrors.fedoraproject.org/mirrorlist?repo=epel-source-${osver[0]}&arch=\$basearch",
    enabled        => 0,
    gpgcheck       => 1,
    failovermethod => 'priority',
    gpgkey         => "file:///etc/pki/rpm-gpg/RPM-GPG-KEY-EPEL-${osver[0]}",
    priority       => 16,
  }

  yum::managed_yumrepo { 'epel-testing':
    descr          => "Extra Packages for Enterprise Linux ${osver[0]} - Testing - \$basearch",
    baseurl        => $baseurl_epel_testing,
    mirrorlist     => "http://mirrors.fedoraproject.org/mirrorlist?repo=testing-epel${osver[0]}&arch=\$basearch",
    enabled        => 0,
    gpgcheck       => 1,
    failovermethod => 'priority',
    gpgkey         => "file:///etc/pki/rpm-gpg/RPM-GPG-KEY-EPEL-${osver[0]}",
    priority       => 17,
  }

  yum::managed_yumrepo { 'epel-testing-debuginfo':
    descr          => "Extra Packages for Enterprise Linux ${osver[0]} - Testing - \$basearch - Debug",
    baseurl        => $baseurl_epel_testing_debuginfo,
    mirrorlist     => "http://mirrors.fedoraproject.org/mirrorlist?repo=testing-debug-epel${osver[0]}&arch=\$basearch",
    enabled        => 0,
    gpgcheck       => 1,
    failovermethod => 'priority',
    gpgkey         => "file:///etc/pki/rpm-gpg/RPM-GPG-KEY-EPEL-${osver[0]}",
    priority       => 17,
  }

  yum::managed_yumrepo { 'epel-testing-source':
    descr          => "Extra Packages for Enterprise Linux ${osver[0]} - Testing - \$basearch - Source",
    baseurl        => $baseurl_epel_testing_source,
    mirrorlist     => "http://mirrors.fedoraproject.org/mirrorlist?repo=testing-source-epel${osver[0]}&arch=\$basearch",
    enabled        => 0,
    gpgcheck       => 1,
    failovermethod => 'priority',
    gpgkey         => "file:///etc/pki/rpm-gpg/RPM-GPG-KEY-EPEL-${osver[0]}",
    priority       => 17,
  }

}

