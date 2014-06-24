# Private class
class mysql::bindings::java {

  package { 'mysql-connector-java':
    ensure   => $mysql::bindings::java_package_ensure,
    name     => $mysql::bindings::java_package_name,
    provider => $mysql::bindings::java_package_provider,
  }

}
