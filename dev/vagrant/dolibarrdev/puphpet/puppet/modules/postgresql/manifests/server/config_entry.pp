# Manage a postgresql.conf entry. See README.md for more details.
define postgresql::server::config_entry (
  $ensure = 'present',
  $value  = undef,
  $path   = false
) {
  $postgresql_conf_path = $postgresql::server::postgresql_conf_path

  $target = $path ? {
    false   => $postgresql_conf_path,
    default => $path,
  }

  case $name {
    /data_directory|hba_file|ident_file|include|listen_addresses|port|max_connections|superuser_reserved_connections|unix_socket_directory|unix_socket_group|unix_socket_permissions|bonjour|bonjour_name|ssl|ssl_ciphers|shared_buffers|max_prepared_transactions|max_files_per_process|shared_preload_libraries|wal_level|wal_buffers|archive_mode|max_wal_senders|hot_standby|logging_collector|silent_mode|track_activity_query_size|autovacuum_max_workers|autovacuum_freeze_max_age|max_locks_per_transaction|max_pred_locks_per_transaction|restart_after_crash|lc_messages|lc_monetary|lc_numeric|lc_time/: {
      Postgresql_conf {
        notify => Class['postgresql::server::service'],
        before => Class['postgresql::server::reload'],
      }
    }

    default: {
      Postgresql_conf {
        notify => Class['postgresql::server::reload'],
      }
    }
  }

  case $ensure {
    /present|absent/: {
      postgresql_conf { $name:
        ensure  => $ensure,
        target  => $target,
        value   => $value,
        require => Class['postgresql::server::initdb'],
      }
    }

    default: {
      fail("Unknown value for ensure '${ensure}'.")
    }
  }
}
