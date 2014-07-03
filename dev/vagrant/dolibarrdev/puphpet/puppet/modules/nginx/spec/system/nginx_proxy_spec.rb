require 'spec_helper_system'

describe "nginx::resource::upstream define:" do
  it 'should run successfully' do

    pp = "
    class { 'nginx': }
    nginx::resource::upstream { 'puppet_rack_app':
      ensure  => present,
      members => [
        'localhost:3000',
        'localhost:3001',
        'localhost:3002',
      ],
    }
    nginx::resource::vhost { 'rack.puppetlabs.com':
      ensure => present,
      proxy  => 'http://puppet_rack_app',
    }
    "

    puppet_apply(pp) do |r|
      [0,2].should include r.exit_code
      r.refresh
      r.stderr.should be_empty
      r.exit_code.should be_zero
    end
  end

  describe file('/etc/nginx/conf.d/puppet_rack_app-upstream.conf') do
   it { should be_file }
   it { should contain "server     localhost:3000" }
   it { should contain "server     localhost:3001" }
   it { should contain "server     localhost:3002" }
   it { should_not contain "server     localhost:3003" }
  end

  describe file('/etc/nginx/sites-available/rack.puppetlabs.com.conf') do
    it { should be_file }
    it { should contain "proxy_pass          http://puppet_rack_app;" }
  end

end
