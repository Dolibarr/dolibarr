class apache::default_mods (
  $all            = true,
  $mods           = undef,
  $apache_version = $::apache::apache_version
) {
  # These are modules required to run the default configuration.
  # They are not configurable at this time, so we just include
  # them to make sure it works.
  case $::osfamily {
    'redhat', 'freebsd': {
      ::apache::mod { 'log_config': }
      if versioncmp($apache_version, '2.4') >= 0 {
        # Lets fork it
        ::apache::mod { 'systemd': }
        ::apache::mod { 'unixd': }
      }
    }
    default: {}
  }
  ::apache::mod { 'authz_host': }

  # The rest of the modules only get loaded if we want all modules enabled
  if $all {
    case $::osfamily {
      'debian': {
        include ::apache::mod::reqtimeout
      }
      'redhat': {
        include ::apache::mod::actions
        include ::apache::mod::cache
        include ::apache::mod::mime
        include ::apache::mod::mime_magic
        include ::apache::mod::vhost_alias
        include ::apache::mod::suexec
        include ::apache::mod::rewrite
        include ::apache::mod::speling
        ::apache::mod { 'auth_digest': }
        ::apache::mod { 'authn_anon': }
        ::apache::mod { 'authn_dbm': }
        ::apache::mod { 'authz_dbm': }
        ::apache::mod { 'authz_owner': }
        ::apache::mod { 'expires': }
        ::apache::mod { 'ext_filter': }
        ::apache::mod { 'include': }
        ::apache::mod { 'logio': }
        ::apache::mod { 'substitute': }
        ::apache::mod { 'usertrack': }
        ::apache::mod { 'version': }

        if versioncmp($apache_version, '2.4') >= 0 {
          ::apache::mod { 'authn_core': }
        }
        else {
          ::apache::mod { 'authn_alias': }
          ::apache::mod { 'authn_default': }
        }
      }
      'freebsd': {
        include ::apache::mod::actions
        include ::apache::mod::cache
        include ::apache::mod::disk_cache
        include ::apache::mod::headers
        include ::apache::mod::info
        include ::apache::mod::mime_magic
        include ::apache::mod::reqtimeout
        include ::apache::mod::rewrite
        include ::apache::mod::userdir
        include ::apache::mod::vhost_alias
        include ::apache::mod::speling

        ::apache::mod { 'asis': }
        ::apache::mod { 'auth_digest': }
        ::apache::mod { 'authn_alias': }
        ::apache::mod { 'authn_anon': }
        ::apache::mod { 'authn_dbm': }
        ::apache::mod { 'authn_default': }
        ::apache::mod { 'authz_dbm': }
        ::apache::mod { 'authz_owner': }
        ::apache::mod { 'cern_meta': }
        ::apache::mod { 'charset_lite': }
        ::apache::mod { 'dumpio': }
        ::apache::mod { 'expires': }
        ::apache::mod { 'file_cache': }
        ::apache::mod { 'filter':}
        ::apache::mod { 'imagemap':}
        ::apache::mod { 'include': }
        ::apache::mod { 'logio': }
        ::apache::mod { 'unique_id': }
        ::apache::mod { 'usertrack': }
        ::apache::mod { 'version': }
      }
      default: {}
    }
    case $::apache::mpm_module {
      'prefork': {
        include ::apache::mod::cgi
      }
      'worker': {
        include ::apache::mod::cgid
      }
      default: {
        # do nothing
      }
    }
    include ::apache::mod::alias
    include ::apache::mod::autoindex
    include ::apache::mod::dav
    include ::apache::mod::dav_fs
    include ::apache::mod::deflate
    include ::apache::mod::dir
    include ::apache::mod::mime
    include ::apache::mod::negotiation
    include ::apache::mod::setenvif
    ::apache::mod { 'auth_basic': }
    ::apache::mod { 'authn_file': }

      if versioncmp($apache_version, '2.4') >= 0 {
      # authz_core is needed for 'Require' directive
      ::apache::mod { 'authz_core':
        id => 'authz_core_module',
      }

      # filter is needed by mod_deflate
      ::apache::mod { 'filter': }

      # lots of stuff seems to break without access_compat
      ::apache::mod { 'access_compat': }
    } else {
      ::apache::mod { 'authz_default': }
    }

    ::apache::mod { 'authz_groupfile': }
    ::apache::mod { 'authz_user': }
    ::apache::mod { 'env': }
  } elsif $mods {
    ::apache::default_mods::load { $mods: }

    if versioncmp($apache_version, '2.4') >= 0 {
      # authz_core is needed for 'Require' directive
      ::apache::mod { 'authz_core':
        id => 'authz_core_module',
      }

      # filter is needed by mod_deflate
      ::apache::mod { 'filter': }
    }
  } else {
    if versioncmp($apache_version, '2.4') >= 0 {
      # authz_core is needed for 'Require' directive
      ::apache::mod { 'authz_core':
        id => 'authz_core_module',
      }

      # filter is needed by mod_deflate
      ::apache::mod { 'filter': }
    }
  }
}
