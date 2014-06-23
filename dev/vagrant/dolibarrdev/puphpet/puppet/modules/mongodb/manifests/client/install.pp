# PRIVATE CLASS: do not call directly
class mongodb::client::install {
  $package_ensure = $mongodb::client::ensure
  $package_name   = $mongodb::client::package_name

  case $package_ensure {
    true:     {
      $my_package_ensure = 'present'
    }
    false:    {
      $my_package_ensure = 'purged'
    }
    'absent': {
      $my_package_ensure = 'purged'
    }
    default:  {
      $my_package_ensure = $package_ensure
    }
  }

  package { 'mongodb_client':
    ensure  => $my_package_ensure,
    name    => $package_name,
    tag     => 'mongodb',
  }
}
