/*
 * This "translates" PHP module package names into system-specific names.
 */

define puphpet::php::module (
  $service_autorestart
){

  $package = $::osfamily ? {
    'Debian' => {
      'mbstring' => false, # Comes packaged with PHP, not available in repos
    },
    'Redhat' => {
      #
    }
  }

  $downcase_name = downcase($name)

  if has_key($package, $downcase_name) {
    $package_name = $package[$downcase_name]
  }
  else {
    $package_name = $name
  }

  if $package_name and ! defined(::Php::Module[$package_name]) {
    ::php::module { $package_name:
      service_autorestart => $service_autorestart,
    }
  }

}
