require 'spec_helper_acceptance'

describe 'apache::mod::passenger class', :unless => UNSUPPORTED_PLATFORMS.include?(fact('osfamily')) do
  case fact('osfamily')
  when 'Debian'
    service_name = 'apache2'
    mod_dir = '/etc/apache2/mods-available/'
    conf_file = "#{mod_dir}passenger.conf"
    load_file = "#{mod_dir}passenger.load"

    case fact('operatingsystem')
    when 'Ubuntu'
      case fact('lsbdistrelease')
      when '10.04'
        passenger_root = '/usr'
        passenger_ruby = '/usr/bin/ruby'
      when '12.04'
        passenger_root = '/usr'
        passenger_ruby = '/usr/bin/ruby'
      when '14.04'
        passenger_root         = '/usr/lib/ruby/vendor_ruby/phusion_passenger/locations.ini'
        passenger_ruby         = '/usr/bin/ruby'
        passenger_default_ruby = '/usr/bin/ruby'
      else
        # This may or may not work on Ubuntu releases other than the above
        passenger_root = '/usr'
        passenger_ruby = '/usr/bin/ruby'
      end
    when 'Debian'
      case fact('lsbdistcodename')
      when 'wheezy'
        passenger_root = '/usr'
        passenger_ruby = '/usr/bin/ruby'
      else
        # This may or may not work on Debian releases other than the above
        passenger_root = '/usr'
        passenger_ruby = '/usr/bin/ruby'
      end
    end

    passenger_module_path = '/usr/lib/apache2/modules/mod_passenger.so'
    rackapp_user = 'www-data'
    rackapp_group = 'www-data'
  when 'RedHat'
    service_name = 'httpd'
    mod_dir = '/etc/httpd/conf.d/'
    conf_file = "#{mod_dir}passenger.conf"
    load_file = "#{mod_dir}passenger.load"
    # sometimes installs as 3.0.12, sometimes as 3.0.19 - so just check for the stable part
    passenger_root = '/usr/lib/ruby/gems/1.8/gems/passenger-3.0.1'
    passenger_ruby = '/usr/bin/ruby'
    passenger_tempdir = '/var/run/rubygem-passenger'
    passenger_module_path = 'modules/mod_passenger.so'
    rackapp_user = 'apache'
    rackapp_group = 'apache'
  end

  pp_rackapp = <<-EOS
          /* a simple ruby rack 'hellow world' app */
          file { '/var/www/passenger':
            ensure  => directory,
            owner   => '#{rackapp_user}',
            group   => '#{rackapp_group}',
            require => Class['apache::mod::passenger'],
          }
          file { '/var/www/passenger/config.ru':
            ensure  => file,
            owner   => '#{rackapp_user}',
            group   => '#{rackapp_group}',
            content => "app = proc { |env| [200, { \\"Content-Type\\" => \\"text/html\\" }, [\\"hello <b>world</b>\\"]] }\\nrun app",
            require => File['/var/www/passenger'] ,
          }
          apache::vhost { 'passenger.example.com':
            port    => '80',
            docroot => '/var/www/passenger/public',
            docroot_group => '#{rackapp_group}' ,
            docroot_owner => '#{rackapp_user}' ,
            custom_fragment => "PassengerRuby  #{passenger_ruby}\\nRailsEnv  development" ,
            require => File['/var/www/passenger/config.ru'] ,
          }
          host { 'passenger.example.com': ip => '127.0.0.1', }
  EOS

  case fact('osfamily')
  when 'Debian'
    context "default passenger config" do
      it 'succeeds in puppeting passenger' do
        pp = <<-EOS
          /* stock apache and mod_passenger */
          class { 'apache': }
          class { 'apache::mod::passenger': }
          #{pp_rackapp}
        EOS
        apply_manifest(pp, :catch_failures => true)
      end

      describe service(service_name) do
        it { should be_enabled }
        it { should be_running }
      end

      describe file(conf_file) do
        it { should contain "PassengerRoot \"#{passenger_root}\"" }

        case fact('operatingsystem')
        when 'Ubuntu'
          case fact('lsbdistrelease')
          when '10.04'
            it { should contain "PassengerRuby \"#{passenger_ruby}\"" }
            it { should_not contain "/PassengerDefaultRuby/" }
          when '12.04'
            it { should contain "PassengerRuby \"#{passenger_ruby}\"" }
            it { should_not contain "/PassengerDefaultRuby/" }
          when '14.04'
            it { should contain "PassengerDefaultRuby \"#{passenger_ruby}\"" }
            it { should_not contain "/PassengerRuby/" }
          else
            # This may or may not work on Ubuntu releases other than the above
            it { should contain "PassengerRuby \"#{passenger_ruby}\"" }
            it { should_not contain "/PassengerDefaultRuby/" }
          end
        when 'Debian'
          case fact('lsbdistcodename')
          when 'wheezy'
            it { should contain "PassengerRuby \"#{passenger_ruby}\"" }
            it { should_not contain "/PassengerDefaultRuby/" }
          else
            # This may or may not work on Debian releases other than the above
            it { should contain "PassengerRuby \"#{passenger_ruby}\"" }
            it { should_not contain "/PassengerDefaultRuby/" }
          end
        end
      end

      describe file(load_file) do
        it { should contain "LoadModule passenger_module #{passenger_module_path}" }
      end

      it 'should output status via passenger-memory-stats' do
        shell("sudo /usr/sbin/passenger-memory-stats") do |r|
          r.stdout.should =~ /Apache processes/
          r.stdout.should =~ /Nginx processes/
          r.stdout.should =~ /Passenger processes/

          # passenger-memory-stats output on Ubuntu 14.04 does not contain
          # these two lines
          unless fact('operatingsystem') == 'Ubuntu' && fact('operatingsystemrelease') == '14.04'
            r.stdout.should =~ /### Processes: [0-9]+/
            r.stdout.should =~ /### Total private dirty RSS: [0-9\.]+ MB/
          end

          r.exit_code.should == 0
        end
      end

      # passenger-status fails under stock ubuntu-server-12042-x64 + mod_passenger,
      # even when the passenger process is successfully installed and running
      unless fact('operatingsystem') == 'Ubuntu' && fact('operatingsystemrelease') == '12.04'
        it 'should output status via passenger-status' do
          # xml output not available on ubunutu <= 10.04, so sticking with default pool output
          shell("sudo /usr/sbin/passenger-status") do |r|
            # spacing may vary
            r.stdout.should =~ /[\-]+ General information [\-]+/
            if fact('operatingsystem') == 'Ubuntu' && fact('operatingsystemrelease') == '14.04'
              r.stdout.should =~ /Max pool size[ ]+: [0-9]+/
              r.stdout.should =~ /Processes[ ]+: [0-9]+/
              r.stdout.should =~ /Requests in top-level queue[ ]+: [0-9]+/
            else
              r.stdout.should =~ /max[ ]+= [0-9]+/
              r.stdout.should =~ /count[ ]+= [0-9]+/
              r.stdout.should =~ /active[ ]+= [0-9]+/
              r.stdout.should =~ /inactive[ ]+= [0-9]+/
              r.stdout.should =~ /Waiting on global queue: [0-9]+/
            end

            r.exit_code.should == 0
          end
        end
      end

      it 'should answer to passenger.example.com' do
        shell("/usr/bin/curl passenger.example.com:80") do |r|
          r.stdout.should =~ /^hello <b>world<\/b>$/
          r.exit_code.should == 0
        end
      end

    end

  when 'RedHat'
    # no fedora 18 passenger package yet, and rhel5 packages only exist for ruby 1.8.5
    unless (fact('operatingsystem') == 'Fedora' and fact('operatingsystemrelease').to_f >= 18) or (fact('osfamily') == 'RedHat' and fact('operatingsystemmajrelease') == '5' and fact('rubyversion') != '1.8.5')

      context "default passenger config" do
        it 'succeeds in puppeting passenger' do
          pp = <<-EOS
            /* EPEL and passenger repositories */
            class { 'epel': }
            exec { 'passenger.repo GPG key':
              command => '/usr/bin/sudo /usr/bin/curl -o /etc/yum.repos.d/RPM-GPG-KEY-stealthymonkeys.asc http://passenger.stealthymonkeys.com/RPM-GPG-KEY-stealthymonkeys.asc',
              creates => '/etc/yum.repos.d/RPM-GPG-KEY-stealthymonkeys.asc',
            }
            file { 'passenger.repo GPG key':
              ensure  => file,
              path    => '/etc/yum.repos.d/RPM-GPG-KEY-stealthymonkeys.asc',
              require => Exec['passenger.repo GPG key'],
            }
            epel::rpm_gpg_key { 'passenger.stealthymonkeys.com':
              path    => '/etc/yum.repos.d/RPM-GPG-KEY-stealthymonkeys.asc',
              require => [
                Class['epel'],
                File['passenger.repo GPG key'],
              ]
            }
            yumrepo { 'passenger':
              baseurl         => 'http://passenger.stealthymonkeys.com/rhel/$releasever/$basearch' ,
              descr           => 'Red Hat Enterprise $releasever - Phusion Passenger',
              enabled         => 1,
              gpgcheck        => 1,
              gpgkey          => 'http://passenger.stealthymonkeys.com/RPM-GPG-KEY-stealthymonkeys.asc',
              mirrorlist      => 'http://passenger.stealthymonkeys.com/rhel/mirrors',
              require => [
                Epel::Rpm_gpg_key['passenger.stealthymonkeys.com'],
              ],
            }
            /* apache and mod_passenger */
            class { 'apache':
                require => [
                  Class['epel'],
              ],
            }
            class { 'apache::mod::passenger':
              require => [
                Yumrepo['passenger']
              ],
            }
            #{pp_rackapp}
          EOS
          apply_manifest(pp, :catch_failures => true)
        end

        describe service(service_name) do
          it { should be_enabled }
          it { should be_running }
        end

        describe file(conf_file) do
          it { should contain "PassengerRoot #{passenger_root}" }
          it { should contain "PassengerRuby #{passenger_ruby}" }
          it { should contain "PassengerTempDir #{passenger_tempdir}" }
        end

        describe file(load_file) do
          it { should contain "LoadModule passenger_module #{passenger_module_path}" }
        end

        it 'should output status via passenger-memory-stats' do
          shell("sudo /usr/bin/passenger-memory-stats") do |r|
            r.stdout.should =~ /Apache processes/
            r.stdout.should =~ /Nginx processes/
            r.stdout.should =~ /Passenger processes/
            r.stdout.should =~ /### Processes: [0-9]+/
            r.stdout.should =~ /### Total private dirty RSS: [0-9\.]+ MB/

            r.exit_code.should == 0
          end
        end

        it 'should output status via passenger-status' do
          shell("sudo PASSENGER_TMPDIR=/var/run/rubygem-passenger /usr/bin/passenger-status") do |r|
            # spacing may vary
            r.stdout.should =~ /[\-]+ General information [\-]+/
            r.stdout.should =~ /max[ ]+= [0-9]+/
            r.stdout.should =~ /count[ ]+= [0-9]+/
            r.stdout.should =~ /active[ ]+= [0-9]+/
            r.stdout.should =~ /inactive[ ]+= [0-9]+/
            r.stdout.should =~ /Waiting on global queue: [0-9]+/

            r.exit_code.should == 0
          end
        end

        it 'should answer to passenger.example.com' do
          shell("/usr/bin/curl passenger.example.com:80") do |r|
            r.stdout.should =~ /^hello <b>world<\/b>$/
            r.exit_code.should == 0
          end
        end
      end

    end

  end
end
