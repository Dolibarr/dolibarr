class puphpet::adminer(
  $location,
  $owner       = 'www-data',
  $php_package = 'php'
) {

  if ! defined(File[$location]) {
    file { $location:
      replace => no,
      ensure  => directory,
      mode    => 775,
      require => Package[$php_package]
    }
  }

  exec{ "download adminer to ${location}":
    command => "wget http://www.adminer.org/latest.php -O ${location}/index.php",
    require => File[$location],
    creates => "${location}/index.php",
    returns => [ 0, 4 ],
  }

}
