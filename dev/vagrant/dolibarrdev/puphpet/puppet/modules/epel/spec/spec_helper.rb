require 'puppetlabs_spec_helper/module_spec_helper'

def default_facts
  {
    :osfamily               => 'RedHat',
    :operatingsystem        => 'CentOS',
  }
end
