define apache::peruser::processor (
  $user,
  $group,
  $file = undef,
) {
  if ! $file {
    $filename = "${name}.conf"
  } else {
    $filename = $file
  }
  file { "${::apache::mod_dir}/peruser/processors/${filename}":
    ensure  => file,
    content => "Processor ${user} ${group}\n",
    require => File["${::apache::mod_dir}/peruser/processors"],
    notify  => Service['httpd'],
  }
}
