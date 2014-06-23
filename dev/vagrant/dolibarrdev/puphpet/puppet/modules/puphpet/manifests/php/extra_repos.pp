/*
 * Some PHP packages require extra repos.
 * ex: PECL mongo is best to fetch Mongo's official repo
 */

define puphpet::php::extra_repos {

  $downcase_name = downcase($name)

  if $downcase_name == 'mongo' and ! defined(Class['mongodb::globals']) {
    class { 'mongodb::globals':
      manage_package_repo => true,
    }
  }

}
