# This installs a MongoDB server. See README.md for more details.
class mongodb::server (
  $ensure           = $mongodb::params::ensure,

  $user             = $mongodb::params::user,
  $group            = $mongodb::params::group,

  $config           = $mongodb::params::config,
  $dbpath           = $mongodb::params::dbpath,
  $pidfilepath      = $mongodb::params::pidfilepath,

  $service_provider = $mongodb::params::service_provider,
  $service_name     = $mongodb::params::service_name,
  $service_status   = $mongodb::params::service_status,

  $package_ensure  = $ensure,
  $package_name    = $mongodb::params::server_package_name,

  $logpath         = $mongodb::params::logpath,
  $bind_ip         = $mongodb::params::bind_ip,
  $logappend       = true,
  $fork            = $mongodb::params::fork,
  $port            = 27017,
  $journal         = $mongodb::params::journal,
  $nojournal       = undef,
  $smallfiles      = undef,
  $cpu             = undef,
  $auth            = false,
  $noauth          = undef,
  $verbose         = undef,
  $verbositylevel  = undef,
  $objcheck        = undef,
  $quota           = undef,
  $quotafiles      = undef,
  $diaglog         = undef,
  $directoryperdb  = undef,
  $profile         = undef,
  $maxconns        = undef,
  $oplog_size      = undef,
  $nohints         = undef,
  $nohttpinterface = undef,
  $noscripting     = undef,
  $notablescan     = undef,
  $noprealloc      = undef,
  $nssize          = undef,
  $mms_token       = undef,
  $mms_name        = undef,
  $mms_interval    = undef,
  $replset         = undef,
  $rest            = undef,
  $slowms          = undef,
  $keyfile         = undef,
  $set_parameter   = undef,
  $syslog          = undef,

  # Deprecated parameters
  $master          = undef,
  $slave           = undef,
  $only            = undef,
  $source          = undef,
) inherits mongodb::params {


  if ($ensure == 'present' or $ensure == true) {
    anchor { 'mongodb::server::start': }->
    class { 'mongodb::server::install': }->
    class { 'mongodb::server::config': }->
    class { 'mongodb::server::service': }->
    anchor { 'mongodb::server::end': }
  } else {
    anchor { 'mongodb::server::start': }->
    class { 'mongodb::server::service': }->
    class { 'mongodb::server::config': }->
    class { 'mongodb::server::install': }->
    anchor { 'mongodb::server::end': }
  }
}
