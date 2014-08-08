require 'spec_helper'

describe 'apache::mod::passenger', :type => :class do
  let :pre_condition do
    'include apache'
  end
  context "on a Debian OS" do
    let :facts do
      {
        :osfamily               => 'Debian',
        :operatingsystemrelease => '6',
        :concat_basedir         => '/dne',
        :lsbdistcodename        => 'squeeze',
        :operatingsystem        => 'Debian',
        :id                     => 'root',
        :kernel                 => 'Linux',
        :path                   => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
      }
    end
    it { should contain_class("apache::params") }
    it { should contain_apache__mod('passenger') }
    it { should contain_package("libapache2-mod-passenger") }
    it { should contain_file('passenger.load').with({
      'path' => '/etc/apache2/mods-available/passenger.load',
    }) }
    it { should contain_file('passenger.conf').with({
      'path' => '/etc/apache2/mods-available/passenger.conf',
    }) }
    it { should contain_file('passenger_package.conf').with_ensure('absent') }
    describe "with passenger_root => '/usr/lib/example'" do
      let :params do
        { :passenger_root => '/usr/lib/example' }
      end
      it { should contain_file('passenger.conf').with_content(%r{PassengerRoot "/usr/lib/example"}) }
    end
    describe "with passenger_ruby => /usr/lib/example/ruby" do
      let :params do
        { :passenger_ruby => '/usr/lib/example/ruby' }
      end
      it { should contain_file('passenger.conf').with_content(%r{PassengerRuby "/usr/lib/example/ruby"}) }
    end
    describe "with passenger_default_ruby => /usr/lib/example/ruby1.9.3" do
      let :params do
        { :passenger_ruby => '/usr/lib/example/ruby1.9.3' }
      end
      it { should contain_file('passenger.conf').with_content(%r{PassengerRuby "/usr/lib/example/ruby1.9.3"}) }
    end
    describe "with passenger_high_performance => on" do
      let :params do
        { :passenger_high_performance => 'on' }
      end
      it { should contain_file('passenger.conf').with_content(/^  PassengerHighPerformance on$/) }
    end
    describe "with passenger_pool_idle_time => 1200" do
      let :params do
        { :passenger_pool_idle_time => 1200 }
      end
      it { should contain_file('passenger.conf').with_content(/^  PassengerPoolIdleTime 1200$/) }
    end
    describe "with passenger_max_requests => 20" do
      let :params do
        { :passenger_max_requests => 20 }
      end
      it { should contain_file('passenger.conf').with_content(/^  PassengerMaxRequests 20$/) }
    end
    describe "with passenger_stat_throttle_rate => 10" do
      let :params do
        { :passenger_stat_throttle_rate => 10 }
      end
      it { should contain_file('passenger.conf').with_content(/^  PassengerStatThrottleRate 10$/) }
    end
    describe "with passenger_max_pool_size => 16" do
      let :params do
        { :passenger_max_pool_size => 16 }
      end
      it { should contain_file('passenger.conf').with_content(/^  PassengerMaxPoolSize 16$/) }
    end
    describe "with rack_autodetect => on" do
      let :params do
        { :rack_autodetect => 'on' }
      end
      it { should contain_file('passenger.conf').with_content(/^  RackAutoDetect on$/) }
    end
    describe "with rails_autodetect => on" do
      let :params do
        { :rails_autodetect => 'on' }
      end
      it { should contain_file('passenger.conf').with_content(/^  RailsAutoDetect on$/) }
    end
    describe "with passenger_use_global_queue => on" do
      let :params do
        { :passenger_use_global_queue => 'on' }
      end
      it { should contain_file('passenger.conf').with_content(/^  PassengerUseGlobalQueue on$/) }
    end
    describe "with mod_path => '/usr/lib/foo/mod_foo.so'" do
      let :params do
        { :mod_path => '/usr/lib/foo/mod_foo.so' }
      end
      it { should contain_file('passenger.load').with_content(/^LoadModule passenger_module \/usr\/lib\/foo\/mod_foo\.so$/) }
    end
    describe "with mod_lib_path => '/usr/lib/foo'" do
      let :params do
        { :mod_lib_path => '/usr/lib/foo' }
      end
      it { should contain_file('passenger.load').with_content(/^LoadModule passenger_module \/usr\/lib\/foo\/mod_passenger\.so$/) }
    end
    describe "with mod_lib => 'mod_foo.so'" do
      let :params do
        { :mod_lib => 'mod_foo.so' }
      end
      it { should contain_file('passenger.load').with_content(/^LoadModule passenger_module \/usr\/lib\/apache2\/modules\/mod_foo\.so$/) }
    end
    describe "with mod_id => 'mod_foo'" do
      let :params do
        { :mod_id => 'mod_foo' }
      end
      it { should contain_file('passenger.load').with_content(/^LoadModule mod_foo \/usr\/lib\/apache2\/modules\/mod_passenger\.so$/) }
    end

    context "with Ubuntu 12.04 defaults" do
      let :facts do
        {
          :osfamily               => 'Debian',
          :operatingsystemrelease => '12.04',
          :operatingsystem        => 'Ubuntu',
          :lsbdistrelease         => '12.04',
          :concat_basedir         => '/dne',
          :id                     => 'root',
          :path                   => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
        }
      end

      it { should contain_file('passenger.conf').with_content(%r{PassengerRoot "/usr"}) }
      it { should contain_file('passenger.conf').with_content(%r{PassengerRuby "/usr/bin/ruby"}) }
      it { should contain_file('passenger.conf').without_content(/PassengerDefaultRuby/) }
    end

    context "with Ubuntu 14.04 defaults" do
      let :facts do
        {
          :osfamily               => 'Debian',
          :operatingsystemrelease => '14.04',
          :operatingsystem        => 'Ubuntu',
          :lsbdistrelease         => '14.04',
          :concat_basedir         => '/dne',
          :id                     => 'root',
          :path                   => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
        }
      end

      it { should contain_file('passenger.conf').with_content(%r{PassengerRoot "/usr/lib/ruby/vendor_ruby/phusion_passenger/locations.ini"}) }
      it { should contain_file('passenger.conf').without_content(/PassengerRuby/) }
      it { should contain_file('passenger.conf').with_content(%r{PassengerDefaultRuby "/usr/bin/ruby"}) }
    end

    context "with Debian 7 defaults" do
      let :facts do
        {
          :osfamily               => 'Debian',
          :operatingsystemrelease => '7.3',
          :operatingsystem        => 'Debian',
          :lsbdistcodename        => 'wheezy',
          :concat_basedir         => '/dne',
          :id                     => 'root',
          :path                   => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
        }
      end

      it { should contain_file('passenger.conf').with_content(%r{PassengerRoot "/usr"}) }
      it { should contain_file('passenger.conf').with_content(%r{PassengerRuby "/usr/bin/ruby"}) }
      it { should contain_file('passenger.conf').without_content(/PassengerDefaultRuby/) }
    end
  end

  context "on a RedHat OS" do
    let :facts do
      {
        :osfamily               => 'RedHat',
        :operatingsystemrelease => '6',
        :concat_basedir         => '/dne',
        :operatingsystem        => 'RedHat',
        :id                     => 'root',
        :kernel                 => 'Linux',
        :path                   => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
      }
    end
    it { should contain_class("apache::params") }
    it { should contain_apache__mod('passenger') }
    it { should contain_package("mod_passenger") }
    it { should contain_file('passenger_package.conf').with({
      'path' => '/etc/httpd/conf.d/passenger.conf',
    }) }
    it { should contain_file('passenger_package.conf').without_content }
    it { should contain_file('passenger_package.conf').without_source }
    it { should contain_file('passenger.load').with({
      'path' => '/etc/httpd/conf.d/passenger.load',
    }) }
    it { should contain_file('passenger.conf').without_content(/PassengerRoot/) }
    it { should contain_file('passenger.conf').without_content(/PassengerRuby/) }
    describe "with passenger_root => '/usr/lib/example'" do
      let :params do
        { :passenger_root => '/usr/lib/example' }
      end
      it { should contain_file('passenger.conf').with_content(/^  PassengerRoot "\/usr\/lib\/example"$/) }
    end
    describe "with passenger_ruby => /usr/lib/example/ruby" do
      let :params do
        { :passenger_ruby => '/usr/lib/example/ruby' }
      end
      it { should contain_file('passenger.conf').with_content(/^  PassengerRuby "\/usr\/lib\/example\/ruby"$/) }
    end
  end
  context "on a FreeBSD OS" do
    let :facts do
      {
        :osfamily               => 'FreeBSD',
        :operatingsystemrelease => '9',
        :concat_basedir         => '/dne',
        :operatingsystem        => 'FreeBSD',
        :id                     => 'root',
        :kernel                 => 'FreeBSD',
        :path                   => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
      }
    end
    it { should contain_class("apache::params") }
    it { should contain_apache__mod('passenger') }
    it { should contain_package("www/rubygem-passenger") }
  end
end
