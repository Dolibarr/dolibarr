# == Class: elasticsearch::package
#
# This class exists to coordinate all software package management related
# actions, functionality and logical units in a central place.
#
#
# === Parameters
#
# This class does not provide any parameters.
#
#
# === Examples
#
# This class may be imported by other classes to use its functionality:
#   class { 'elasticsearch::package': }
#
# It is not intended to be used directly by external resources like node
# definitions or other modules.
#
#
# === Authors
#
# * Richard Pijnenburg <mailto:richard@ispavailability.com>
#
class elasticsearch::package {


  #### Package management

  # set params: in operation
  if $elasticsearch::ensure == 'present' {

    # Check if we want to install a specific version or not
    if $elasticsearch::version == false {

      $package_ensure = $elasticsearch::autoupgrade ? {
        true  => 'latest',
        false => 'present',
      }

    } else {

      # install specific version
      $package_ensure = $elasticsearch::version

    }

    # action
    if ($elasticsearch::package_url != undef) {

      $package_dir = $elasticsearch::package_dir

      # Create directory to place the package file
      exec { 'create_package_dir_elasticsearch':
        cwd     => '/',
        path    => ['/usr/bin', '/bin'],
        command => "mkdir -p ${elasticsearch::package_dir}",
        creates => $elasticsearch::package_dir;
      }

      file { $package_dir:
        ensure  => 'directory',
        purge   => $elasticsearch::purge_package_dir,
        force   => $elasticsearch::purge_package_dir,
        require => Exec['create_package_dir_elasticsearch'],
      }

      $filenameArray = split($elasticsearch::package_url, '/')
      $basefilename = $filenameArray[-1]

      $sourceArray = split($elasticsearch::package_url, ':')
      $protocol_type = $sourceArray[0]

      $extArray = split($basefilename, '\.')
      $ext = $extArray[-1]

      case $protocol_type {

        puppet: {

          file { "${package_dir}/${basefilename}":
            ensure  => present,
            source  => $elasticsearch::package_url,
            require => File[$package_dir],
            backup  => false,
            before  => Package[$elasticsearch::params::package]
          }

        }
        ftp, https, http: {

          exec { 'download_package_elasticsearch':
            command => "${elasticsearch::params::dlcmd} ${package_dir}/${basefilename} ${elasticsearch::package_url} 2> /dev/null",
            path    => ['/usr/bin', '/bin'],
            creates => "${package_dir}/${basefilename}",
            require => File[$package_dir],
            before  => Package[$elasticsearch::params::package]
          }

        }
        file: {

          $source_path = $sourceArray[1]
          file { "${package_dir}/${basefilename}":
            ensure  => present,
            source  => $source_path,
            require => File[$package_dir],
            backup  => false,
            before  => Package[$elasticsearch::params::package]
          }

        }
        default: {
          fail("Protocol must be puppet, file, http, https, or ftp. You have given \"${protocol_type}\"")
        }
      }

      case $ext {
        'deb':   { $pkg_provider = 'dpkg' }
        'rpm':   { $pkg_provider = 'rpm'  }
        default: { fail("Unknown file extention \"${ext}\".") }
      }

      $pkg_source = "${package_dir}/${basefilename}"

    } else {
      $pkg_source = undef
      $pkg_provider = undef
    }

  # Package removal
  } else {

    $pkg_source = undef
    $pkg_provider = undef
    $package_ensure = 'purged'
  }

  package { $elasticsearch::params::package:
    ensure   => $package_ensure,
    source   => $pkg_source,
    provider => $pkg_provider
  }

}
