# == Define Resource Type: apache::balancermember
#
# This type will setup a balancer member inside a listening service
# configuration block in /etc/apache/apache.cfg on the load balancer.
# currently it only has the ability to specify the instance name, url and an
# array of options. More features can be added as needed. The best way to
# implement this is to export this resource for all apache balancer member
# servers, and then collect them on the main apache load balancer.
#
# === Requirement/Dependencies:
#
# Currently requires the puppetlabs/concat module on the Puppet Forge and
# uses storeconfigs on the Puppet Master to export/collect resources
# from all balancer members.
#
# === Parameters
#
# [*name*]
# The title of the resource is arbitrary and only utilized in the concat
# fragment name.
#
# [*balancer_cluster*]
# The apache service's instance name (or, the title of the apache::balancer
# resource). This must match up with a declared apache::balancer resource.
#
# [*url*]
# The url used to contact the balancer member server.
#
# [*options*]
# An array of options to be specified after the url.
#
# === Examples
#
# Exporting the resource for a balancer member:
#
# @@apache::balancermember { 'apache':
#   balancer_cluster => 'puppet00',
#   url              => "ajp://${::fqdn}:8009"
#   options          => ['ping=5', 'disablereuse=on', 'retry=5', 'ttl=120'],
# }
#
define apache::balancermember(
  $balancer_cluster,
  $url = "http://${::fqdn}/",
  $options = [],
) {

  concat::fragment { "BalancerMember ${url}":
    ensure  => present,
    target  => "${::apache::params::confd_dir}/balancer_${balancer_cluster}.conf",
    content => inline_template(" BalancerMember ${url} <%= @options.join ' ' %>\n"),
  }
}
