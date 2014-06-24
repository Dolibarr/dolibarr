require 'spec_helper'

describe 'apache::mod::itk', :type => :class do
  let :pre_condition do
    'class { "apache": mpm_module => false, }'
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
    it { should_not contain_apache__mod('itk') }
    it { should contain_file("/etc/apache2/mods-available/itk.conf").with_ensure('file') }
    it { should contain_file("/etc/apache2/mods-enabled/itk.conf").with_ensure('link') }

    context "with Apache version < 2.4" do
      let :params do
        {
          :apache_version => '2.2',
        }
      end

      it { should_not contain_file("/etc/apache2/mods-available/itk.load") }
      it { should_not contain_file("/etc/apache2/mods-enabled/itk.load") }

      it { should contain_package("apache2-mpm-itk") }
    end

    context "with Apache version >= 2.4" do
      let :params do
        {
          :apache_version => '2.4',
        }
      end

      it { should contain_file("/etc/apache2/mods-available/itk.load").with({
        'ensure'  => 'file',
        'content' => "LoadModule mpm_itk_module /usr/lib/apache2/modules/mod_mpm_itk.so\n"
        })
      }
      it { should contain_file("/etc/apache2/mods-enabled/itk.load").with_ensure('link') }
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
    it { should_not contain_apache__mod('itk') }
    it { should contain_file("/usr/local/etc/apache22/Modules/itk.conf").with_ensure('file') }
  end
end
