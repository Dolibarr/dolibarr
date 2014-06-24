require 'spec_helper_system'

describe "nginx::resource::vhost define:" do
  context 'new vhost on port 80' do
    it 'should configure a nginx vhost' do

      pp = "
      class { 'nginx': }
      nginx::resource::vhost { 'www.puppetlabs.com':
        ensure   => present,
        www_root => '/var/www/www.puppetlabs.com',
      }
      host { 'www.puppetlabs.com': ip => '127.0.0.1', }
      file { ['/var/www','/var/www/www.puppetlabs.com']: ensure => directory }
      file { '/var/www/www.puppetlabs.com/index.html': ensure  => file, content => 'Hello from www\n', }
      "

      puppet_apply(pp) do |r|
        [0,2].should include r.exit_code
        r.refresh
        r.stderr.should be_empty
        r.exit_code.should be_zero
      end
    end

    describe file('/etc/nginx/sites-available/www.puppetlabs.com.conf') do
      it { should be_file }
      it { should contain "www.puppetlabs.com" }
    end

    describe file('/etc/nginx/sites-enabled/www.puppetlabs.com.conf') do
      it { should be_linked_to '/etc/nginx/sites-available/www.puppetlabs.com.conf' }
    end

    describe service('nginx') do
      it { should be_running }
    end

    it 'should answer to www.puppetlabs.com' do
      shell("/usr/bin/curl http://www.puppetlabs.com:80") do |r|
        r.stdout.should == "Hello from www\n"
        r.exit_code.should be_zero
      end
    end
  end

  context 'should run successfully with ssl' do
    it 'should configure a nginx SSL vhost' do

      pp = "
      class { 'nginx': }
      nginx::resource::vhost { 'www.puppetlabs.com':
        ensure   => present,
        ssl      => true,
        ssl_cert => '/tmp/blah.cert',
        ssl_key  => '/tmp/blah.key',
        www_root => '/var/www/www.puppetlabs.com',
      }
      host { 'www.puppetlabs.com': ip => '127.0.0.1', }
      file { ['/var/www','/var/www/www.puppetlabs.com']: ensure => directory }
      file { '/var/www/www.puppetlabs.com/index.html': ensure  => file, content => 'Hello from www\n', }
      "

      puppet_apply(pp) do |r|
        [0,2].should include r.exit_code
        r.refresh
        r.stderr.should be_empty
        r.exit_code.should be_zero
      end
    end

    describe file('/etc/nginx/sites-available/www.puppetlabs.com.conf') do
      it { should be_file }
      it { should contain "ssl on;" }
    end

    describe file('/etc/nginx/sites-enabled/www.puppetlabs.com.conf') do
      it { should be_linked_to '/etc/nginx/sites-available/www.puppetlabs.com.conf' }
    end

    describe service('nginx') do
      it { should be_running }
    end

    it 'should answer to http://www.puppetlabs.com' do
      shell("/usr/bin/curl http://www.puppetlabs.com:80") do |r|
        r.stdout.should == "Hello from www\n"
        r.exit_code.should == 0
      end
    end

    it 'should answer to https://www.puppetlabs.com' do
      # use --insecure because it's a self-signed cert
      shell("/usr/bin/curl --insecure https://www.puppetlabs.com:443") do |r|
        r.stdout.should == "Hello from www\n"
        r.exit_code.should == 0
      end
    end
  end
end
