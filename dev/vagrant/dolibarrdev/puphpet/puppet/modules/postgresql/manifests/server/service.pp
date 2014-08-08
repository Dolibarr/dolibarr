# PRIVATE CLASS: do not call directly
class postgresql::server::service {
  $ensure           = $postgresql::server::ensure
  $service_name     = $postgresql::server::service_name
  $service_provider = $postgresql::server::service_provider
  $service_status   = $postgresql::server::service_status
  $user             = $postgresql::server::user
  $default_database = $postgresql::server::default_database

  $service_ensure = $ensure ? {
    present => true,
    absent  => false,
    default => $ensure
  }

  anchor { 'postgresql::server::service::begin': }

  service { 'postgresqld':
    ensure    => $service_ensure,
    name      => $service_name,
    enable    => $service_ensure,
    provider  => $service_provider,
    hasstatus => true,
    status    => $service_status,
  }

  if($service_ensure) {
    # This blocks the class before continuing if chained correctly, making
    # sure the service really is 'up' before continuing.
    #
    # Without it, we may continue doing more work before the database is
    # prepared leading to a nasty race condition.
    postgresql::validate_db_connection { 'validate_service_is_running':
      run_as          => $user,
      database_name   => $default_database,
      sleep           => 1,
      tries           => 60,
      create_db_first => false,
      require         => Service['postgresqld'],
      before          => Anchor['postgresql::server::service::end']
    }
  }

  anchor { 'postgresql::server::service::end': }
}
