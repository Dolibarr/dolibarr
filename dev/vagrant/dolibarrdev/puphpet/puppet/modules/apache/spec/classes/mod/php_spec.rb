require 'spec_helper'

describe 'apache::mod::php', :type => :class do
  describe "on a Debian OS" do
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
    context "with mpm_module => prefork" do
      let :pre_condition do
        'class { "apache": mpm_module => prefork, }'
      end
      it { should contain_class("apache::params") }
      it { should contain_apache__mod('php5') }
      it { should contain_package("libapache2-mod-php5") }
      it { should contain_file("php5.load").with(
        :content => "LoadModule php5_module /usr/lib/apache2/modules/libphp5.so\n"
      ) }
    end
    context 'with mpm_module => worker' do
      let :pre_condition do
        'class { "apache": mpm_module => worker, }'
      end
      it 'should raise an error' do
        expect { subject }.to raise_error Puppet::Error, /mpm_module => 'prefork'/
      end
    end
  end
  describe "on a RedHat OS" do
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
    context "with default params" do
      let :pre_condition do
        'class { "apache": }'
      end
      it { should contain_class("apache::params") }
      it { should contain_apache__mod('php5') }
      it { should contain_package("php") }
      it { should contain_file("php5.load").with(
        :content => "LoadModule php5_module modules/libphp5.so\n"
      ) }
    end
    context "with alternative package name" do let :pre_condition do
        'class { "apache": }'
      end
      let :params do
        { :package_name => 'php54'}
      end
      it { should contain_package("php54") }
    end
    context "with alternative path" do let :pre_condition do
        'class { "apache": }'
      end
      let :params do
        { :path => 'alternative-path'}
      end
      it { should contain_file("php5.load").with(
        :content => "LoadModule php5_module alternative-path\n"
      ) }
    end
    context "with alternative extensions" do let :pre_condition do
        'class { "apache": }'
      end
      let :params do
        { :extensions => ['.php','.php5']}
      end
      it { should contain_file("php5.conf").with_content(/AddHandler php5-script .php .php5\n/) }
    end
    context "with specific version" do
      let :pre_condition do
        'class { "apache": }'
      end
      let :params do
        { :package_ensure => '5.3.13'}
      end
      it { should contain_package("php").with(
        :ensure => '5.3.13'
      ) }
    end
    context "with mpm_module => prefork" do
      let :pre_condition do
        'class { "apache": mpm_module => prefork, }'
      end
      it { should contain_class("apache::params") }
      it { should contain_apache__mod('php5') }
      it { should contain_package("php") }
      it { should contain_file("php5.load").with(
        :content => "LoadModule php5_module modules/libphp5.so\n"
      ) }
    end
  end
  describe "on a FreeBSD OS" do
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
    context "with mpm_module => prefork" do
      let :pre_condition do
        'class { "apache": mpm_module => prefork, }'
      end
      it { should contain_class('apache::params') }
      it { should contain_apache__mod('php5') }
      it { should contain_package("lang/php5") }
      it { should contain_file('php5.load') }
    end
    # FIXME: not sure about the following context
    context 'with mpm_module => worker' do
      let :pre_condition do
        'class { "apache": mpm_module => worker, }'
      end
      it 'should raise an error' do
        expect { subject.should contain_apache__mod('php5') }.to raise_error Puppet::Error, /mpm_module => 'prefork'/
      end
    end
  end
  describe "OS independent tests" do
    let :facts do
      {
        :osfamily               => 'Debian',
        :operatingsystemrelease => '6',
        :concat_basedir         => '/dne',
      }
    end
    context 'with content param' do
      let :pre_condition do
        'class { "apache": mpm_module => prefork, }'
      end
      let :params do
        { :content => 'somecontent' }
      end
      it { should contain_file('php5.conf').with(
        :content => 'somecontent'
      ) }
    end
    context 'with template param' do
      let :pre_condition do
        'class { "apache": mpm_module => prefork, }'
      end
      let :params do
        { :template => 'apache/mod/php5.conf.erb' }
      end
      it { should contain_file('php5.conf').with(
        :content => /^# PHP is an HTML-embedded scripting language which attempts to make it/
      ) }
    end
    context 'with source param' do
      let :pre_condition do
        'class { "apache": mpm_module => prefork, }'
      end
      let :params do
        { :source => 'some-path' }
      end
      it { should contain_file('php5.conf').with(
        :source => 'some-path'
      ) }
    end
    context 'content has priority over template' do
      let :pre_condition do
        'class { "apache": mpm_module => prefork, }'
      end
      let :params do
        {
          :template => 'apache/mod/php5.conf.erb',
          :content  => 'somecontent'
        }
      end
      it { should contain_file('php5.conf').with(
        :content => 'somecontent'
      ) }
    end
    context 'source has priority over template' do
      let :pre_condition do
        'class { "apache": mpm_module => prefork, }'
      end
      let :params do
        {
          :template => 'apache/mod/php5.conf.erb',
          :source   => 'some-path'
        }
      end
      it { should contain_file('php5.conf').with(
        :source => 'some-path'
      ) }
    end
    context 'source has priority over content' do
      let :pre_condition do
        'class { "apache": mpm_module => prefork, }'
      end
      let :params do
        {
          :content => 'somecontent',
          :source  => 'some-path'
        }
      end
      it { should contain_file('php5.conf').with(
        :source => 'some-path'
      ) }
    end
  end
end
