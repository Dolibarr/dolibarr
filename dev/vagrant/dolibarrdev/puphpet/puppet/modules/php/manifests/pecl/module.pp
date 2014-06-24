# Define: php::pecl::module
#
# Installs the defined php pecl component
#
# == Parameters
#
# [*service_autorestart*]
#   wathever we want a module installation notify a service to restart.
#
# [*service*]
#   Service to restart.
#
# [*use_package*]
#   Tries to install pecl module with the relevant package.
#   If set to "no" it installs the module via pecl command. Default: true
#
# [*preferred_state*]
#   Define which preferred state to use when installing Pear modules via pecl
#   command line (when use_package=no). Default: true
#
# [*auto_answer*]
#   The answer(s) to give to pecl prompts for unattended install
#
# [*verbose*]
#   (Optional) - If you want to see verbose pecl output during installation.
#   This can help to debug installation problems (missing packages) during
#   installation process. Default: false
#
# == Examples
# php::pecl::module { 'intl': }
#
# This will install xdebug from pecl source instead of using the package
#
# php::pecl::module { 'xdebug':.
#   use_package => "no",
# }
#
define php::pecl::module (
  $service_autorestart = $php::bool_service_autorestart,
  $service             = $php::service,
  $use_package         = 'yes',
  $preferred_state     = 'stable',
  $auto_answer         = '\\n',
  $ensure              = present,
  $path                = '/usr/bin:/usr/sbin:/bin:/sbin',
  $verbose             = false,
  $version             = '',
  $config_file         = $php::config_file) {

  include php
  include php::pear
  include php::devel

  $manage_service_autorestart = $service_autorestart ? {
    true    => $service ? {
      ''      => undef,
      default => "Service[${service}]",
    },
    false   => undef,
    undef   => undef,
  }

  $real_package_name = $::operatingsystem ? {
    ubuntu  => "php5-${name}",
    debian  => "php5-${name}",
    default => "php-${name}",
  }

  case $use_package {
    yes: {
      package { "php-${name}":
        ensure => $ensure,
        name   => $real_package_name,
        notify => $manage_service_autorestart,
      }
    }
    default: {

      $bool_verbose = any2bool($verbose)

      $pecl_exec_logoutput = $bool_verbose ? {
        true  => true,
        false => undef,
      }

      if $version != '' {
        $new_version = "-${version}"
      } else {
        $new_version = ''
      }

      $pecl_exec_command = $ensure ? {
        present => "printf \"${auto_answer}\" | pecl -d preferred_state=${preferred_state} install ${name}${new_version}",
        absent  => "pecl uninstall -n ${name}",
      }

      $pecl_exec_unless = $ensure ? {
        present => "pecl info ${name}",
        absent  => undef
      }

      $pecl_exec_onlyif = $ensure ? {
        present => undef,
        absent  => "pecl info ${name}",
      }

      exec { "pecl-${name}":
        command   => $pecl_exec_command,
        unless    => $pecl_exec_unless,
        onlyif    => $pecl_exec_onlyif,
        logoutput => $pecl_exec_logoutput,
        path      => $path,
        require   => [ Class['php::pear'], Class['php::devel']],
      }
      if $php::bool_augeas == true {
        php::augeas { "augeas-${name}":
          ensure => $ensure,
          entry  => "PHP/extension[. = \"${name}.so\"]",
          value  => "${name}.so",
          notify => $manage_service_autorestart,
          target => $config_file,
        }
      }
    }
  } # End Case
}
