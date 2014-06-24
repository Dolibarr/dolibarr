# Class: apache::version
#
# Try to automatically detect the version by OS
#
class apache::version {
  # This will be 5 or 6 on RedHat, 6 or wheezy on Debian, 12 or quantal on Ubuntu, 3 on Amazon, etc.
  $osr_array = split($::operatingsystemrelease,'[\/\.]')
  $distrelease = $osr_array[0]
  if ! $distrelease {
    fail("Class['apache::params']: Unparsable \$::operatingsystemrelease: ${::operatingsystemrelease}")
  }

  case $::osfamily {
    'RedHat': {
      if ($::operatingsystem == 'Fedora' and $distrelease >= 18) or ($::operatingsystem != 'Fedora' and $distrelease >= 7) {
        $default = '2.4'
      } else {
        $default = '2.2'
      }
    }
    'Debian': {
      if $::operatingsystem == 'Ubuntu' and $::operatingsystemrelease >= 13.10 {
        $default = '2.4'
      } else {
        $default = '2.2'
      }
    }
    'FreeBSD': {
      $default = '2.2'
    }
    default: {
      fail("Class['apache::version']: Unsupported osfamily: ${::osfamily}")
    }
  }
}
