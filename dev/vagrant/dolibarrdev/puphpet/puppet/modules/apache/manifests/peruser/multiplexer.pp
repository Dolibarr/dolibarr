define apache::peruser::multiplexer (
  $user = $::apache::user,
  $group = $::apache::group,
  $file = undef,
) {
  if ! $file {
    $filename = "${name}.conf"
  } else {
    $filename = $file
  }
  file { "${::apache::mod_dir}/peruser/multiplexers/${filename}":
    ensure  => file,
    content => "Multiplexer ${user} ${group}\n",
    require => File["${::apache::mod_dir}/peruser/multiplexers"],
    notify  => Service['httpd'],
  }
}
