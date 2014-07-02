class { 'mongodb::globals': manage_package_repo => true }->
class { 'mongodb::server': }
