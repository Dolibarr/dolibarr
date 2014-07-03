# == Define: elasticsearch::ruby
#
# there are many ruby bindings for elasticsearch. This provides all
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
# elasticsearch::ruby { 'elasticsearch':; }
#
#
# === Authors
#
# * Richard Pijnenburg <mailto:richard@ispavailability.com>
#
define elasticsearch::ruby (
  $ensure = 'present'
) {

  if ! ($ensure in [ 'present', 'absent' ]) {
    fail("\"${ensure}\" is not a valid ensure parameter value")
  }

  # make sure the package name is valid and setup the provider as
  # necessary
  case $name {
    'tire': {
      $provider = 'gem'
    }
    'stretcher': {
      $provider = 'gem'
    }
    'elastic_searchable': {
      $provider = 'gem'
    }
    'elasticsearch': {
      $provider = 'gem'
    }
    default: {
      fail("unknown ruby client package '${name}'")
    }
  }

  package { $name:
    ensure   => $ensure,
    provider => $provider,
  }

}
