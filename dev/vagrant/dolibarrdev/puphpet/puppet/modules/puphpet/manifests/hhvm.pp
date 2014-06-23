# This depends on
#   puppetlabs/apt: https://github.com/puppetlabs/puppetlabs-apt
#   example42/puppet-yum: https://github.com/example42/puppet-yum
#   puppetlabs/puppetlabs-apache: https://github.com/puppetlabs/puppetlabs-apache (if apache)

class puphpet::hhvm(
  $nightly = false,
  $webserver
) {

  $real_webserver = $webserver ? {
    'apache'  => 'apache2',
    'httpd'   => 'apache2',
    'apache2' => 'apache2',
    'nginx'   => 'nginx',
    'fpm'     => 'fpm',
    'cgi'     => 'cgi',
    'fcgi'    => 'cgi',
    'fcgid'   => 'cgi',
    undef     => undef,
  }

  if $nightly == true {
    $package_name_base = $puphpet::params::hhvm_package_name_nightly
  } else {
    $package_name_base = $puphpet::params::hhvm_package_name
  }

  if $nightly == true and $::osfamily == 'Redhat' {
    warning('HHVM-nightly is not available for RHEL distros. Falling back to normal release')
  }

  case $::operatingsystem {
    'debian': {
      if $::lsbdistcodename != 'wheezy' {
        fail('Sorry, HHVM currently only works with Debian 7+.')
      }

      $sources_list = '/etc/apt/sources.list'

      $deb_srcs = [
        'deb http://http.us.debian.org/debian wheezy main',
        'deb-src http://http.us.debian.org/debian wheezy main',
        'deb http://security.debian.org/ wheezy/updates main',
        'deb-src http://security.debian.org/ wheezy/updates main',
        'deb http://http.us.debian.org/debian wheezy-updates main',
        'deb-src http://http.us.debian.org/debian wheezy-updates main'
      ]

      each( $deb_srcs ) |$value| {
        exec { "add contrib non-free to ${value}":
          cwd     => '/etc/apt',
          command => "perl -p -i -e 's#${value}#${value} contrib non-free#gi' ${sources_list}",
          unless  => "grep -Fxq '${value} contrib non-free' ${sources_list}",
          path    => [ '/bin/', '/sbin/', '/usr/bin/', '/usr/sbin/' ],
          notify  => Exec['apt_update']
        }
      }
    }
    'ubuntu': {
      if ! ($lsbdistcodename in ['precise', 'raring', 'trusty']) {
        fail('Sorry, HHVM currently only works with Ubuntu 12.04, 13.10 and 14.04.')
      }

      apt::key { '5D50B6BA': key_server => 'hkp://keyserver.ubuntu.com:80' }

      if $lsbdistcodename in ['lucid', 'precise'] {
        apt::ppa { 'ppa:mapnik/boost': require => Apt::Key['5D50B6BA'], options => '' }
      }
    }
    'centos': {
      yum::managed_yumrepo { 'hop5':
        descr    => 'hop5 repository',
        baseurl  => 'http://www.hop5.in/yum/el6/',
        gpgkey   => 'file:///etc/pki/rpm-gpg/RPM-GPG-KEY-HOP5',
        enabled  => 1,
        gpgcheck => 0,
        priority => 1
      }
    }
  }

  if $real_webserver == 'apache2' {
    if ! defined(Class['apache::mod::mime']) {
      class { 'apache::mod::mime': }
    }
    if ! defined(Class['apache::mod::fastcgi']) {
      class { 'apache::mod::fastcgi': }
    }
    if ! defined(Class['apache::mod::alias']) {
      class { 'apache::mod::alias': }
    }
    if ! defined(Class['apache::mod::proxy']) {
      class { 'apache::mod::proxy': }
    }
    if ! defined(Class['apache::mod::proxy_http']) {
      class { 'apache::mod::proxy_http': }
    }
    if ! defined(Apache::Mod['actions']) {
      apache::mod{ 'actions': }
    }
  }

  $os = downcase($::operatingsystem)

  case $::osfamily {
    'debian': {
      apt::key { 'hhvm':
        key        => '16d09fb4',
        key_source => 'http://dl.hhvm.com/conf/hhvm.gpg.key',
      }

      apt::source { 'hhvm':
        location          => "http://dl.hhvm.com/${os}",
        repos             => 'main',
        required_packages => 'debian-keyring debian-archive-keyring',
        include_src       => false,
        require           => Apt::Key['hhvm']
      }
    }
  }

  ensure_packages( [ $package_name_base ] )

}
