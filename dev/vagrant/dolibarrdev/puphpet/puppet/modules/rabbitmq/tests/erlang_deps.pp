# install first the garethr-erlang module. See README.md
include 'erlang'

class { 'erlang': epel_enable => true}
Class['erlang'] -> Class['rabbitmq']
