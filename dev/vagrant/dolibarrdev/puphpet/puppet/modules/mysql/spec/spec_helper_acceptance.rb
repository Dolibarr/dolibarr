require 'beaker-rspec'

UNSUPPORTED_PLATFORMS = [ 'Windows', 'Solaris', 'AIX' ]

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
    puppet_module_install(:source => proj_root, :module_name => 'mysql')
    hosts.each do |host|
      # Required for binding tests.
      if fact('osfamily') == 'RedHat'
        version = fact("operatingsystemmajrelease")
        shell("yum localinstall -y http://yum.puppetlabs.com/puppetlabs-release-el-#{version}.noarch.rpm")
        if version == '6'
          shell("yum localinstall -y http://mirror.pnl.gov/epel/6/i386/epel-release-6-8.noarch.rpm")
        elsif version == '5'
          shell("yum localinstall -y http://mirrors.servercentral.net/fedora/epel/5/i386/epel-release-5-4.noarch.rpm")
        else
          puts "Sorry, this version is not supported."
          exit
        end
      end

      shell("/bin/touch #{default['distmoduledir']}/hiera.yaml")
      shell('puppet module install puppetlabs-stdlib --version 3.2.0', { :acceptable_exit_codes => [0,1] })
    end
  end
end
