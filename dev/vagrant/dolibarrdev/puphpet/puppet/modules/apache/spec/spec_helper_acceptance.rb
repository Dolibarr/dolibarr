require 'beaker-rspec/spec_helper'
require 'beaker-rspec/helpers/serverspec'


unless ENV['RS_PROVISION'] == 'no'
  hosts.each do |host|
    if host['platform'] =~ /debian/
      on host, 'echo \'export PATH=/var/lib/gems/1.8/bin/:${PATH}\' >> ~/.bashrc'
    end
    if host.is_pe?
      install_pe
    else
      install_puppet
      on host, "mkdir -p #{host['distmoduledir']}"
    end
  end
end

UNSUPPORTED_PLATFORMS = ['Suse','windows','AIX','Solaris']

RSpec.configure do |c|
  # Project root
  proj_root = File.expand_path(File.join(File.dirname(__FILE__), '..'))

  # Readable test descriptions
  c.formatter = :documentation

  # Configure all nodes in nodeset
  c.before :suite do
    # Install module and dependencies
    puppet_module_install(:source => proj_root, :module_name => 'apache')
    hosts.each do |host|
      # Required for mod_passenger tests.
      if fact('osfamily') == 'RedHat'
        on host, puppet('module','install','stahnma/epel'), { :acceptable_exit_codes => [0,1] }
      end
      # Required for manifest to make mod_pagespeed repository available
      if fact('osfamily') == 'Debian'
        on host, puppet('module','install','puppetlabs-apt'), { :acceptable_exit_codes => [0,1] }
      end
      on host, puppet('module','install','puppetlabs-stdlib'), { :acceptable_exit_codes => [0,1] }
      on host, puppet('module','install','puppetlabs-concat'), { :acceptable_exit_codes => [0,1] }
    end
  end
end
