/*
 * This "translates" PECL package names into system-specific names.
 * For example, APCu does not install correctly on CentOS via PECL,
 * but there is a system package for it that works well. Use that
 * instead of the PECL package.
 */

define puphpet::php::pecl (
  $service_autorestart
){

  $ignore = {
    'date_time' => true,
    'mysql'     => true,
  }

  $pecl = $::osfamily ? {
    'Debian' => {
      'mongo' => $::lsbdistcodename ? {
        'precise' => 'mongo',
        default   => false,
      },
    },
    'Redhat' => {
      #
    }
  }

  $pecl_beta = $::osfamily ? {
    'Debian' => {
      'augeas'      => 'augeas',
      'zendopcache' => $::operatingsystem ? {
        'debian' => false,
        'ubuntu' => 'ZendOpcache',
      },
    },
    'Redhat' => {
      #
    }
  }

  $package = $::osfamily ? {
    'Debian' => {
      'apc'         => $::operatingsystem ? {
        'debian' => 'php5-apc',
        'ubuntu' => 'php5-apcu',
      },
      'apcu'        => 'php5-apcu',
      'imagick'     => 'php5-imagick',
      'memcache'    => 'php5-memcache',
      'memcached'   => 'php5-memcached',
      'mongo'       => $::lsbdistcodename ? {
        'precise' => false,
        default   => 'php5-mongo',
      },
      'zendopcache' => 'php5-zendopcache',
    },
    'Redhat' => {
      'apc'         => 'php-pecl-apcu',
      'apcu'        => 'php-pecl-apcu',
      'imagick'     => 'php-pecl-imagick',
      'memcache'    => 'php-pecl-memcache',
      'memcached'   => 'php-pecl-memcached',
      'mongo'       => 'php-pecl-mongo',
      'zendopcache' => 'php-pecl-zendopcache',
    }
  }

  $auto_answer_hash = {
    'mongo' => 'no\n'
  }

  $downcase_name = downcase($name)

  if has_key($auto_answer_hash, $downcase_name) {
    $auto_answer = $auto_answer_hash[$downcase_name]
  } else {
    $auto_answer = '\\n'
  }

  if has_key($ignore, $downcase_name) and $ignore[$downcase_name] {
    $pecl_name       = $pecl[$downcase_name]
    $package_name    = false
    $preferred_state = 'stable'
  }
  elsif has_key($pecl, $downcase_name) and $pecl[$downcase_name] {
    $pecl_name       = $pecl[$downcase_name]
    $package_name    = false
    $preferred_state = 'stable'
  }
  elsif has_key($pecl_beta, $downcase_name) and $pecl_beta[$downcase_name] {
    $pecl_name       = $pecl_beta[$downcase_name]
    $package_name    = false
    $preferred_state = 'beta'
  }
  elsif has_key($package, $downcase_name) and $package[$downcase_name] {
    $pecl_name    = false
    $package_name = $package[$downcase_name]
  }
  else {
    $pecl_name    = $name
    $package_name = false
  }

  if $pecl_name and ! defined(::Php::Pecl::Module[$pecl_name]) {
    ::php::pecl::module { $pecl_name:
      use_package         => false,
      preferred_state     => $preferred_state,
      auto_answer         => $auto_answer,
      service_autorestart => $service_autorestart,
    }
  }
  elsif $package_name and ! defined(Package[$package_name]) {
    package { $package_name:
      ensure  => present,
      require => Class['Php::Devel'],
    }
  }

}
