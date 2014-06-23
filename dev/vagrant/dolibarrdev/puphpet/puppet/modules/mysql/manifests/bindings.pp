# See README.md.
class mysql::bindings (
  # Boolean to determine if we should include the classes.
  $java_enable   = false,
  $perl_enable   = false,
  $php_enable    = false,
  $python_enable = false,
  $ruby_enable   = false,
  # Settings for the various classes.
  $java_package_ensure     = $mysql::params::java_package_ensure,
  $java_package_name       = $mysql::params::java_package_name,
  $java_package_provider   = $mysql::params::java_package_provider,
  $perl_package_ensure     = $mysql::params::perl_package_ensure,
  $perl_package_name       = $mysql::params::perl_package_name,
  $perl_package_provider   = $mysql::params::perl_package_provider,
  $php_package_ensure      = $mysql::params::php_package_ensure,
  $php_package_name        = $mysql::params::php_package_name,
  $php_package_provider    = $mysql::params::php_package_provider,
  $python_package_ensure   = $mysql::params::python_package_ensure,
  $python_package_name     = $mysql::params::python_package_name,
  $python_package_provider = $mysql::params::python_package_provider,
  $ruby_package_ensure     = $mysql::params::ruby_package_ensure,
  $ruby_package_name       = $mysql::params::ruby_package_name,
  $ruby_package_provider   = $mysql::params::ruby_package_provider
) inherits mysql::params {

  if $java_enable   { include '::mysql::bindings::java' }
  if $perl_enable   { include '::mysql::bindings::perl' }
  if $php_enable    { include '::mysql::bindings::php' }
  if $python_enable { include '::mysql::bindings::python' }
  if $ruby_enable   { include '::mysql::bindings::ruby' }

}
