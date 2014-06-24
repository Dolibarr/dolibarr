require 'spec_helper'

describe 'apache::mod::peruser', :type => :class do
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
    it { should_not contain_apache__mod('peruser') }
    it { should contain_file("/usr/local/etc/apache22/Modules/peruser.conf").with_ensure('file') }
  end
end
