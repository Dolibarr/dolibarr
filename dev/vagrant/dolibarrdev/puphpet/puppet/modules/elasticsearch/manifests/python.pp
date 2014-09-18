# == Define: elasticsearch::python
#
# there are many python bindings for elasticsearch. This provides all
# the ones we know about http://www.elasticsearch.org/guide/clients/
#
#
# === Parameters
#
# [*ensure*]
#   String. Controls if the managed resources shall be <tt>present</tt> or
#   <tt>absent</tt>. If set to <tt>absent</tt>:
#   * The managed software packages are being uninstalled.
#   * Any traces of the packages will be purged as good as possible. This may
#     include existing configuration files. The exact behavior is provider
#     dependent. Q.v.:
#     * Puppet type reference: {package, "purgeable"}[http://j.mp/xbxmNP]
#     * {Puppet's package provider source code}[http://j.mp/wtVCaL]
#   * System modifications (if any) will be reverted as good as possible
#     (e.g. removal of created users, services, changed log settings, ...).
#   * This is thus destructive and should be used with care.
#   Defaults to <tt>present</tt>.

#
#
# === Examples
#
# elasticsearch::python { 'pyes':; }
#
#
# === Authors
#
# * Richard Pijnenburg <mailto:richard@ispavailability.com>
#
define elasticsearch::python (
  $ensure = 'present'
) {

  if ! ($ensure in [ 'present', 'absent' ]) {
    fail("\"${ensure}\" is not a valid ensure parameter value")
  }

  # make sure the package name is valid and setup the provider as
  # necessary
  case $name {
    'pyes': {
      $provider = 'pip'
    }
    'rawes': {
      $provider = 'pip'
    }
    'pyelasticsearch': {
      $provider = 'pip'
    }
    'ESClient': {
      $provider = 'pip'
    }
    'elasticutils': {
      $provider = 'pip'
    }
    'elasticsearch': {
      $provider = 'pip'
    }
    default: {
      fail("unknown python binding package '${name}'")
    }
  }

  package { $name:
    ensure   => $ensure,
    provider => $provider,
  }

}
