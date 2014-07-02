require 'spec_helper_acceptance'

describe 'apache::mod::fcgid class', :unless => UNSUPPORTED_PLATFORMS.include?(fact('osfamily')) do
  case fact('osfamily')
  when 'Debian'
    # Not implemented
  when 'RedHat'
    context "default fcgid config" do
      it 'succeeds in puppeting fcgid' do
        pp = <<-EOS
          class { 'epel': } # mod_fcgid lives in epel
          class { 'apache': }
          class { 'apache::mod::php': } # For /usr/bin/php-cgi
          class { 'apache::mod::fcgid':
            options => {
              'FcgidIPCDir'  => '/var/run/fcgidsock',
            },
          }
          apache::vhost { 'fcgid.example.com':
            port        => '80',
            docroot     => '/var/www/fcgid',
            directories => {
              path        => '/var/www/fcgid',
              options     => '+ExecCGI',
              addhandlers => {
                handler    => 'fcgid-script',
                extensions => '.php',
              },
              fcgiwrapper => {
                command => '/usr/bin/php-cgi',
                suffix  => '.php',
              }
            },
          }
          file { '/var/www/fcgid/index.php':
            ensure  => file,
            owner   => 'root',
            group   => 'root',
            content => "<?php echo 'Hello world'; ?>\\n",
          }
        EOS
        apply_manifest(pp, :catch_failures => true)
      end

      describe service('httpd') do
        it { should be_enabled }
        it { should be_running }
      end

      it 'should answer to fcgid.example.com' do
        shell("/usr/bin/curl -H 'Host: fcgid.example.com' 127.0.0.1:80") do |r|
          r.stdout.should =~ /^Hello world$/
          r.exit_code.should == 0
        end
      end

      it 'should run a php-cgi process' do
        shell("pgrep -u apache php-cgi", :acceptable_exit_codes => [0])
      end
    end
  end
end
