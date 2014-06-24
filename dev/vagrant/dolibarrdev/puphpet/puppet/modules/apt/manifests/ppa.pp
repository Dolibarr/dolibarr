# ppa.pp

define apt::ppa(
  $release = $::lsbdistcodename,
  $options = $apt::params::ppa_options,
) {
  $ensure  = 'present'
  include apt::params
  include apt::update

  $sources_list_d = $apt::params::sources_list_d

  if ! $release {
    fail('lsbdistcodename fact not available: release parameter required')
  }

  if $::operatingsystem != 'Ubuntu' {
    fail("apt::ppa is currently supported on Ubuntu only.")
  }

  $filename_without_slashes = regsubst($name, '/', '-', 'G')
  $filename_without_dots    = regsubst($filename_without_slashes, '\.', '_', 'G')
  $filename_without_ppa     = regsubst($filename_without_dots, '^ppa:', '', 'G')
  $sources_list_d_filename  = "${filename_without_ppa}-${release}.list"

  if $ensure == 'present' {
    $package = $::lsbdistrelease ? {
        /^[1-9]\..*|1[01]\..*|12.04$/ => 'python-software-properties',
        default  => 'software-properties-common',
    }

    if ! defined(Package[$package]) {
        package { $package: }
    }

    if defined(Class[apt]) {
        $proxy_host = $apt::proxy_host
        $proxy_port = $apt::proxy_port
        case  $proxy_host {
        false, '': {
            $proxy_env = []
        }
        default: {$proxy_env = ["http_proxy=http://${proxy_host}:${proxy_port}", "https_proxy=http://${proxy_host}:${proxy_port}"]}
        }
    } else {
        $proxy_env = []
    }
    exec { "add-apt-repository-${name}":
        environment  => $proxy_env,
        command      => "/usr/bin/add-apt-repository ${options} ${name}",
        unless       => "/usr/bin/test -s ${sources_list_d}/${sources_list_d_filename}",
        user         => 'root',
        logoutput    => 'on_failure',
        notify       => Exec['apt_update'],
        require      => [
        File['sources.list.d'],
        Package[$package],
        ],
    }

    file { "${sources_list_d}/${sources_list_d_filename}":
        ensure  => file,
        require => Exec["add-apt-repository-${name}"],
    }
  }
  else {

    file { "${sources_list_d}/${sources_list_d_filename}":
        ensure => 'absent',
        mode   => '0644',
        owner  => 'root',
        gruop  => 'root',
        notify => Exec['apt_update'],
    }
  }

  # Need anchor to provide containment for dependencies.
  anchor { "apt::ppa::${name}":
    require => Class['apt::update'],
  }
}
