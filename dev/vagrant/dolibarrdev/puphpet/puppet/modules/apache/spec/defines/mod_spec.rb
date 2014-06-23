require 'spec_helper'

describe 'apache::mod', :type => :define do
  let :pre_condition do
    'include apache'
  end
  context "on a RedHat osfamily" do
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

    describe "for non-special modules" do
      let :title do
        'spec_m'
      end
      it { should contain_class("apache::params") }
      it "should manage the module load file" do
        should contain_file('spec_m.load').with({
          :path    => '/etc/httpd/conf.d/spec_m.load',
          :content => "LoadModule spec_m_module modules/mod_spec_m.so\n",
          :owner   => 'root',
          :group   => 'root',
          :mode    => '0644',
        } )
      end
    end

    describe "with shibboleth module and package param passed" do
      # name/title for the apache::mod define
      let :title do
        'xsendfile'
      end
      # parameters
      let(:params) { {:package => 'mod_xsendfile'} }

      it { should contain_class("apache::params") }
      it { should contain_package('mod_xsendfile') }
    end
  end

  context "on a Debian osfamily" do
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

    describe "for non-special modules" do
      let :title do
        'spec_m'
      end
      it { should contain_class("apache::params") }
      it "should manage the module load file" do
        should contain_file('spec_m.load').with({
          :path    => '/etc/apache2/mods-available/spec_m.load',
          :content => "LoadModule spec_m_module /usr/lib/apache2/modules/mod_spec_m.so\n",
          :owner   => 'root',
          :group   => 'root',
          :mode    => '0644',
        } )
      end
      it "should link the module load file" do
        should contain_file('spec_m.load symlink').with({
          :path   => '/etc/apache2/mods-enabled/spec_m.load',
          :target => '/etc/apache2/mods-available/spec_m.load',
          :owner   => 'root',
          :group   => 'root',
          :mode    => '0644',
        } )
      end
    end
  end

  context "on a FreeBSD osfamily" do
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

    describe "for non-special modules" do
      let :title do
        'spec_m'
      end
      it { should contain_class("apache::params") }
      it "should manage the module load file" do
        should contain_file('spec_m.load').with({
          :path    => '/usr/local/etc/apache22/Modules/spec_m.load',
          :content => "LoadModule spec_m_module /usr/local/libexec/apache22/mod_spec_m.so\n",
          :owner   => 'root',
          :group   => 'wheel',
          :mode    => '0644',
        } )
      end
    end
  end
end
