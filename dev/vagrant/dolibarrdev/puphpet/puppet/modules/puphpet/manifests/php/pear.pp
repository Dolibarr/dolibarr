/*
 * This returns PEAR packages requiring beta stability
 */

define puphpet::php::pear (
  $service_name        = '',
  $service_autorestart,
){

  $package = {
    'auth_sasl2'                => 'beta',
    'config_lite'               => 'beta',
    'console_getoptplus'        => 'beta',
    'mdb2_driver_mysql'         => false,
    'mdb2_driver_mysqli'        => false,
    'pear_command_packaging'    => 'alpha',
    'pear_frontend_gtk2'        => false,
    'php_beautifier'            => 'beta',
    'php_parser'                => 'alpha',
    'php_parser_docblockparser' => 'alpha',
    'soap'                      => 'beta',
    'testing_selenium'          => 'beta',
    'versioncontrol_git'        => 'alpha',
    'versioncontrol_svn'        => 'alpha',
    'xml_parser2'               => 'beta',
    'xml_util2'                 => 'alpha',
  }

  $downcase_name = downcase($name)

  if has_key($package, $downcase_name) {
    $package_name    = $name
    $preferred_state = $package[$downcase_name]
  }
  else {
    $package_name    = $name
    $preferred_state = 'stable'
  }

  if $package_name and $preferred_state and ! defined(::Php::Pear::Module[$package_name]) {
    ::php::pear::module { $name:
      use_package         => false,
      preferred_state     => $preferred_state,
      service             => $service_name,
      service_autorestart => $service_autorestart,
    }
  }

}
