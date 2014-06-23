require 'spec_helper'

describe 'apache::mod::event', :type => :class do
  let :pre_condition do
    'class { "apache": mpm_module => false, }'
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
    it { should_not contain_apache__mod('event') }
    it { should contain_file("/usr/local/etc/apache22/Modules/event.conf").with_ensure('file') }
  end
  context "on a Debian OS" do
    let :facts do
      {
        :lsbdistcodename        => 'squeeze',
        :osfamily               => 'Debian',
        :operatingsystemrelease => '6',
        :concat_basedir         => '/dne',
        :operatingsystem        => 'Debian',
        :id                     => 'root',
        :kernel                 => 'Linux',
        :path                   => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
      }
    end

    it { should contain_class("apache::params") }
    it { should_not contain_apache__mod('event') }
    it { should contain_file("/etc/apache2/mods-available/event.conf").with_ensure('file') }
    it { should contain_file("/etc/apache2/mods-enabled/event.conf").with_ensure('link') }

    context "with Apache version < 2.4" do
      let :params do
        {
          :apache_version => '2.2',
        }
      end

      it { should_not contain_file("/etc/apache2/mods-available/event.load") }
      it { should_not contain_file("/etc/apache2/mods-enabled/event.load") }

      it { should contain_package("apache2-mpm-event") }
    end

    context "with Apache version >= 2.4" do
      let :params do
        {
          :apache_version => '2.4',
        }
      end

      it { should contain_file("/etc/apache2/mods-available/event.load").with({
        'ensure'  => 'file',
        'content' => "LoadModule mpm_event_module /usr/lib/apache2/modules/mod_mpm_event.so\n"
        })
      }
      it { should contain_file("/etc/apache2/mods-enabled/event.load").with_ensure('link') }
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

    context "with Apache version >= 2.4" do
      let :params do
        {
          :apache_version => '2.4',
        }
      end

      it { should contain_class("apache::params") }
      it { should_not contain_apache__mod('worker') }
      it { should_not contain_apache__mod('prefork') }

      it { should contain_file("/etc/httpd/conf.d/event.conf").with_ensure('file') }

      it { should contain_file("/etc/httpd/conf.d/event.load").with({
        'ensure'  => 'file',
        'content' => "LoadModule mpm_event_module modules/mod_mpm_event.so\n",
        })
      }
    end
  end
end
