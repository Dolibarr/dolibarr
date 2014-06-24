require 'beaker-rspec'

UNSUPPORTED_PLATFORMS = [ 'windows', 'Solaris' ]

unless ENV['RS_PROVISION'] == 'no'
  hosts.each do |host|
    # Install Puppet
    if host.is_pe?
      install_pe
    else
      install_package host, 'rubygems'
      on host, 'gem install puppet --no-ri --no-rdoc'
      on host, "mkdir -p #{host['distmoduledir']}"
    end
  end
end

RSpec.configure do |c|
  # Project root
  proj_root = File.expand_path(File.join(File.dirname(__FILE__), '..'))

  # Readable test descriptions
  c.formatter = :documentation

  # Configure all nodes in nodeset
  c.before :suite do
    # Install module and dependencies
    puppet_module_install(:source => proj_root, :module_name => 'ntp')
    hosts.each do |host|
      shell("/bin/touch #{default['puppetpath']}/hiera.yaml")
      shell('puppet module install puppetlabs-stdlib', :acceptable_exit_codes => [0,1])
    end
  end
end
