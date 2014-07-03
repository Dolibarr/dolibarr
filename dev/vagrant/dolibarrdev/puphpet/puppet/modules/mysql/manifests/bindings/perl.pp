# Private class
class mysql::bindings::perl {

  package{ 'perl_mysql':
    ensure   => $mysql::bindings::perl_package_ensure,
    name     => $mysql::bindings::perl_package_name,
    provider => $mysql::bindings::perl_package_provider,
  }

}
