# = Class: yum::repo::sl5
#
# Base Scientific Linux 5 repos
#
# == Parameters:
#
# [*mirror_url*]
#   A clean URL to a mirror of `http://ftp.scientificlinux.org/linux/scientific/`.
#   The paramater is interpolated with the known directory structure to
#   create a the final baseurl parameter for each yumrepo so it must be
#   "clean", i.e., without a query string like `?key1=valA&key2=valB`.
#   Additionally, it may not contain a trailing slash.
#   Example: `http://mirror.example.com/pub/rpm/scientific`
#   Default: `undef`
#
class yum::repo::sl5 (
  $mirror_url = undef,
) {

  if $mirror_url {
    validate_re(
      $mirror_url,
      '^(?:https?|ftp):\/\/[\da-zA-Z-][\da-zA-Z\.-]*\.[a-zA-Z]{2,6}\.?(?:\/[\w~-]*)*$',
      '$mirror must be a Clean URL with no query-string, a fully-qualified hostname and no trailing slash.'
    )
  }

  $baseurl_sl5x = $mirror_url ? {
    undef   => undef,
    default => "${mirror_url}/5x/\$basearch/os/",
  }

  $baseurl_sl5x_security = $mirror_url ? {
    undef   => undef,
    default => "${mirror_url}/5x/\$basearch/updates/security/",
  }

  $baseurl_sl5x_fastbugs = $mirror_url ? {
    undef   => undef,
    default => "${mirror_url}/5x/\$basearch/updates/fastbugs/",
  }

  yum::managed_yumrepo { 'sl5x':
    descr          => 'Scientific Linux 5x - $basearch',
    baseurl        => $baseurl_sl5x,
    mirrorlist     => 'http://ftp.scientificlinux.org/linux/scientific/mirrorlist/sl-base-5x.txt',
    failovermethod => 'priority',
    enabled        => 1,
    gpgcheck       => 1,
    gpgkey         => 'file:///etc/pki/rpm-gpg/RPM-GPG-KEY-sl file:///etc/pki/rpm-gpg/RPM-GPG-KEY-dawson',
    gpgkey_source  => 'puppet:///modules/yum/rpm-gpg/RPM-GPG-KEY-sl',
    priority       => 10,
  }

  yum::managed_yumrepo { 'sl5x-security':
    descr          => 'Scientific Linux 5x - $basearch - security updates',
    baseurl        => $baseurl_sl5x_security,
    mirrorlist     => 'http://ftp.scientificlinux.org/linux/scientific/mirrorlist/sl-security-5x.txt',
    failovermethod => 'priority',
    enabled        => 1,
    gpgcheck       => 1,
    gpgkey         => 'file:///etc/pki/rpm-gpg/RPM-GPG-KEY-sl file:///etc/pki/rpm-gpg/RPM-GPG-KEY-dawson',
    priority       => 10,
  }

  yum::managed_yumrepo { 'sl5x-fastbugs':
    descr          => 'Scientific Linux 5x - $basearch - fastbug updates',
    baseurl        => $baseurl_sl5x_fastbugs,
    mirrorlist     => 'http://ftp.scientificlinux.org/linux/scientific/mirrorlist/sl-fastbugs-5x.txt',
    failovermethod => 'priority',
    enabled        => 0,
    gpgcheck       => 1,
    gpgkey         => 'file:///etc/pki/rpm-gpg/RPM-GPG-KEY-sl file:///etc/pki/rpm-gpg/RPM-GPG-KEY-dawson',
    priority       => 10,
  }

}
