# Private class: See README.md
class mysql::bindings::php {

  package { 'php-mysql':
    ensure   => $mysql::bindings::php_package_ensure,
    name     => $mysql::bindings::php_package_name,
    provider => $mysql::bindings::php_package_provider,
  }

}
