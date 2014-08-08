require 'spec_helper_system'

describe "nginx::resource::mailhost define:" do
  it 'should run successfully' do

    pp = "
    class { 'nginx':
      mail => true,
    }
    nginx::resource::vhost { 'www.puppetlabs.com':
      ensure   => present,
      www_root => '/var/www/www.puppetlabs.com',
    }
    nginx::resource::mailhost { 'domain1.example':
      ensure      => present,
      auth_http   => 'localhost/cgi-bin/auth',
      protocol    => 'smtp',
      listen_port => 587,
      ssl_port    => 465,
      xclient     => 'off',
    }
    "

    puppet_apply(pp) do |r|
      [0,2].should include r.exit_code
      r.refresh
      # Not until deprecated variables fixed.
      #r.stderr.should be_empty
      r.exit_code.should be_zero
    end
  end

  describe file('/etc/nginx/conf.mail.d/domain1.example.conf') do
   it { should be_file }
   it { should contain "auth_http             localhost/cgi-bin/auth;" }
  end

  describe file('/etc/nginx/sites-available/www.puppetlabs.com.conf') do
    it { should be_file }
  end

end
