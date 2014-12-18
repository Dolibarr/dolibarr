# Define epel::rpm_gpg_key
#
# Actions:
#   Import a RPM gpg key
#
# Parameters:
#
# [*path*]
#   Path of the RPM GPG key to import
#
# Reqiures:
#   You should probably be on an Enterprise Linux variant. (Centos, RHEL, Scientific, Oracle, Ascendos, et al)
#
# Sample Usage:
#  epel::rpm_gpg_key{ "EPEL-6":
#    path => "/etc/pki/rpm-gpg/RPM-GPG-KEY-EPEL-6"
#  }
#
define epel::rpm_gpg_key($path) {
  # Given the path to a key, see if it is imported, if not, import it
  exec {  "import-${name}":
    path      => '/bin:/usr/bin:/sbin:/usr/sbin',
    command   => "rpm --import ${path}",
    unless    => "rpm -q gpg-pubkey-$(echo $(gpg --throw-keyids < ${path}) | cut --characters=11-18 | tr '[A-Z]' '[a-z]')",
    require   => File[$path],
    logoutput => 'on_failure',
  }
}
