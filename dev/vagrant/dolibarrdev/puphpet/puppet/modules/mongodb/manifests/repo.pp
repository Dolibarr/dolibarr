# PRIVATE CLASS: do not use directly
class mongodb::repo (
  $ensure  = $mongodb::params::ensure,
) inherits mongodb::params {
  case $::osfamily {
    'RedHat', 'Linux': {
      $location = $::architecture ? {
        'x86_64' => 'http://downloads-distro.mongodb.org/repo/redhat/os/x86_64/',
        'i686'   => 'http://downloads-distro.mongodb.org/repo/redhat/os/i686/',
        'i386'   => 'http://downloads-distro.mongodb.org/repo/redhat/os/i686/',
        default  => undef
      }
      class { 'mongodb::repo::yum': }
    }

    'Debian': {
      $location = $::operatingsystem ? {
        'Debian' => 'http://downloads-distro.mongodb.org/repo/debian-sysvinit',
        'Ubuntu' => 'http://downloads-distro.mongodb.org/repo/ubuntu-upstart',
        default  => undef
      }
      class { 'mongodb::repo::apt': }
    }

    default: {
      if($ensure == 'present' or $ensure == true) {
        fail("Unsupported managed repository for osfamily: ${::osfamily}, operatingsystem: ${::operatingsystem}, module ${module_name} currently only supports managing repos for osfamily RedHat, Debian and Ubuntu")
      }
    }
  }
}
