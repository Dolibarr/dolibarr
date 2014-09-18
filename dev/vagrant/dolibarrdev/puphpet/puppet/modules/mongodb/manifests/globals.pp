# Class for setting cross-class global overrides. See README.md for more
# details.

class mongodb::globals (
  $server_package_name  = undef,
  $client_package_name  = undef,

  $service_name         = undef,
  $service_provider     = undef,
  $service_status       = undef,

  $user                 = undef,
  $group                = undef,
  $bind_ip              = undef,

  $version              = undef,

  $manage_package_repo  = undef,
) {

  # Setup of the repo only makes sense globally, so we are doing it here.
  if($manage_package_repo) {
    class { '::mongodb::repo':
      ensure  => present,
    }
  }
}
