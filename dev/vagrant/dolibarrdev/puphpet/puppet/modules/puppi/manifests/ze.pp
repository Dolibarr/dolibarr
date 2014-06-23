# Define puppi::ze
#
# The Puppi 2.0 define that transforms any class variable in data
# you can use with Puppi
#
# == Usage
# Basic Usage:
# puppi::ze { "openssh":
#   variables => get_class_args(),
# }
#
# puppi::ze { "openssh":
#   variables => get_class_args(),
#   filter    => '.*content.*|.*key.*',
# }
#
define puppi::ze (
  $variables,
  $helper = 'standard',
  $filter = '.*content.*|.*password.*',
  $ensure = 'present' ) {

  require puppi
  require puppi::params

  file { "puppize_${name}":
    ensure  => $ensure,
    path    => "${puppi::params::datadir}/${helper}_${name}.yml",
    mode    => '0644',
    owner   => $puppi::params::configfile_owner,
    group   => $puppi::params::configfile_group,
    content => inline_template("---\n<%= Hash[@variables].reject{ |k,v| k.to_s =~ /(${filter})/ }.keys.sort.map{|k| Array({k => @variables[k]}.to_yaml)[1..-1].join}.join(\"\n\") %>\n"),
  }

}
