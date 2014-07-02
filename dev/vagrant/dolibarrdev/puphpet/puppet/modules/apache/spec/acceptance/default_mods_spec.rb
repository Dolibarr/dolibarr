require 'spec_helper_acceptance'

case fact('osfamily')
when 'RedHat'
  mod_dir     = '/etc/httpd/conf.d'
  servicename = 'httpd'
when 'Debian'
  mod_dir     = '/etc/apache2/mods-available'
  servicename = 'apache2'
when 'FreeBSD'
  mod_dir     = '/usr/local/etc/apache22/Modules'
  servicename = 'apache22'
end

describe 'apache::default_mods class', :unless => UNSUPPORTED_PLATFORMS.include?(fact('osfamily')) do
  describe 'no default mods' do
    # Using puppet_apply as a helper
    it 'should apply with no errors' do
      pp = <<-EOS
        class { 'apache':
          default_mods => false,
        }
      EOS

      # Run it twice and test for idempotency
      apply_manifest(pp, :catch_failures => true)
      expect(apply_manifest(pp, :catch_failures => true).exit_code).to be_zero
    end

    describe service(servicename) do
      it { should be_running }
    end
  end

  describe 'no default mods and failing' do
    # Using puppet_apply as a helper
    it 'should apply with errors' do
      pp = <<-EOS
        class { 'apache':
          default_mods => false,
        }
        apache::vhost { 'defaults.example.com':
          docroot => '/var/www/defaults',
          aliases => {
            alias => '/css',
            path  => '/var/www/css',
          },
          setenv  => 'TEST1 one',
        }
      EOS

      apply_manifest(pp, { :expect_failures => true })
    end

    # Are these the same?
    describe service(servicename) do
      it { should_not be_running }
    end
    describe "service #{servicename}" do
      it 'should not be running' do
        shell("pidof #{servicename}", {:acceptable_exit_codes => 1})
      end
    end
  end

  describe 'alternative default mods' do
    # Using puppet_apply as a helper
    it 'should apply with no errors' do
      pp = <<-EOS
        class { 'apache':
          default_mods => [
            'info',
            'alias',
            'mime',
            'env',
            'expires',
          ],
        }
        apache::vhost { 'defaults.example.com':
          docroot => '/var/www/defaults',
          aliases => {
            alias => '/css',
            path  => '/var/www/css',
          },
          setenv  => 'TEST1 one',
        }
      EOS

      apply_manifest(pp, :catch_failures => true)
      shell('sleep 10')
      expect(apply_manifest(pp, :catch_failures => true).exit_code).to be_zero
    end

    describe service(servicename) do
      it { should be_running }
    end
  end

  describe 'change loadfile name' do
    it 'should apply with no errors' do
      pp = <<-EOS
        class { 'apache': default_mods => false }
        ::apache::mod { 'auth_basic': 
          loadfile_name => 'zz_auth_basic.load',
        }
      EOS
      # Run it twice and test for idempotency
      apply_manifest(pp, :catch_failures => true)
      expect(apply_manifest(pp, :catch_failures => true).exit_code).to be_zero
    end

    describe service(servicename) do
      it { should be_running }
    end

    describe file("#{mod_dir}/zz_auth_basic.load") do
      it { should be_file }
    end
  end
end
