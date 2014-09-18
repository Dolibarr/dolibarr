# OS specific parameters
class staging::params {
  case $::osfamily {
    default: {
      $path      = '/opt/staging'
      $owner     = '0'
      $group     = '0'
      $mode      = '0755'
      $exec_path = '/usr/local/bin:/usr/bin:/bin'
    }
    'Solaris': {
      $path      = '/opt/staging'
      $owner     = '0'
      $group     = '0'
      $mode      = '0755'
      $exec_path = '/usr/local/bin:/usr/bin:/bin:/usr/sfw/bin'
    }
    'windows': {
      $path      = $::staging_windir
      $owner     = undef
      $group     = undef
      $mode      = '0755'
      $exec_path = $::path
    }
  }
}
