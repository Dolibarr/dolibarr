require 'puppetlabs_spec_helper/module_spec_helper'

RSpec.configure do |c|
  c.default_facts = {
    :kernel          => 'Linux',
    :concat_basedir  => '/var/lib/puppet/concat',
  }
end
