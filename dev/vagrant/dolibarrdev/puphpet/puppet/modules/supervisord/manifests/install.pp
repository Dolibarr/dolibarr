# Class supervisord::install
#
# Installs supervisor package (defaults to using pip)
#
class supervisord::install inherits supervisord {
  package { 'supervisor':
    ensure   => $supervisord::package_ensure,
    provider => $supervisord::package_provider
  }
}
