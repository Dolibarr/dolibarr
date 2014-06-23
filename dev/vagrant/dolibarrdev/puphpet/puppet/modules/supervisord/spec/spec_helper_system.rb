require 'rspec-system/spec_helper'
require 'rspec-system-puppet/helpers'

include RSpecSystemPuppet::Helpers

RSpec.configure do |c|
  # Project root
  proj_root = File.expand_path(File.join(File.dirname(__FILE__), '..'))

  # Enable colour
  c.tty = true

  # This is where we 'setup' the nodes before running our tests
  c.before :suite do
    # Install puppet
    puppet_install
    puppet_master_install

    # Replace mymodule with your module name
    puppet_module_install(:source => proj_root, :module_name => 'supervisord')
    shell('puppet module install puppetlabs/stdlib')
    shell('puppet module install puppetlabs/concat')

  end
end
