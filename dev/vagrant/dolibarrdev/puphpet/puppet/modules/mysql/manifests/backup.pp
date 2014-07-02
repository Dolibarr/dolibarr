# Deprecated class
class mysql::backup (
  $backupuser,
  $backuppassword,
  $backupdir,
  $backupcompress = true,
  $backuprotate = 30,
  $delete_before_dump = false,
  $backupdatabases = [],
  $file_per_database = false,
  $ensure = 'present',
  $time = ['23', '5'],
) {

  crit("This class has been deprecated and callers should directly call
  mysql::server::backup now.")

  class { 'mysql::server::backup':
    ensure             => $ensure,
    backupuser         => $backupuser,
    backuppassword     => $backuppassword,
    backupdir          => $backupdir,
    backupcompress     => $backupcompress,
    backuprotate       => $backuprotate,
    delete_before_dump => $delete_before_dump,
    backupdatabases    => $backupdatabases,
    file_per_database  => $file_per_database,
    time               => $time,
  }

}
