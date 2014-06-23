class rabbitmq::install {

  $package_ensure   = $rabbitmq::package_ensure
  $package_name     = $rabbitmq::package_name
  $package_provider = $rabbitmq::package_provider
  $package_source   = $rabbitmq::package_source

  package { 'rabbitmq-server':
    ensure   => $package_ensure,
    name     => $package_name,
    provider => $package_provider,
    notify   => Class['rabbitmq::service'],
  }

  if $package_source {
    Package['rabbitmq-server'] {
      source  => $package_source,
    }
  }

}
