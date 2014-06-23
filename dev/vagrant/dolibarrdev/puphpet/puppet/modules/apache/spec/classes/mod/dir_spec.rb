require 'spec_helper'

describe 'apache::mod::dir', :type => :class do
  let :pre_condition do
    'class { "apache":
      default_mods => false,
    }'
  end
  context "on a Debian OS" do
    let :facts do
      {
        :osfamily               => 'Debian',
        :operatingsystemrelease => '6',
        :concat_basedir         => '/dne',
        :operatingsystem        => 'Debian',
        :id                     => 'root',
        :kernel                 => 'Linux',
        :path                   => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
        :lsbdistcodename        => 'squeeze',
      }
    end
    context "passing no parameters" do
      it { should contain_class("apache::params") }
      it { should contain_apache__mod('dir') }
      it { should contain_file('dir.conf').with_content(/^DirectoryIndex /) }
      it { should contain_file('dir.conf').with_content(/ index\.html /) }
      it { should contain_file('dir.conf').with_content(/ index\.html\.var /) }
      it { should contain_file('dir.conf').with_content(/ index\.cgi /) }
      it { should contain_file('dir.conf').with_content(/ index\.pl /) }
      it { should contain_file('dir.conf').with_content(/ index\.php /) }
      it { should contain_file('dir.conf').with_content(/ index\.xhtml$/) }
    end
    context "passing indexes => ['example.txt','fearsome.aspx']" do
      let :params do
        {:indexes => ['example.txt','fearsome.aspx']}
      end
      it { should contain_file('dir.conf').with_content(/ example\.txt /) }
      it { should contain_file('dir.conf').with_content(/ fearsome\.aspx$/) }
    end
  end
  context "on a RedHat OS" do
    let :facts do
      {
        :osfamily               => 'RedHat',
        :operatingsystemrelease => '6',
        :concat_basedir         => '/dne',
        :operatingsystem        => 'Redhat',
        :id                     => 'root',
        :kernel                 => 'Linux',
        :path                   => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
      }
    end
    context "passing no parameters" do
      it { should contain_class("apache::params") }
      it { should contain_apache__mod('dir') }
      it { should contain_file('dir.conf').with_content(/^DirectoryIndex /) }
      it { should contain_file('dir.conf').with_content(/ index\.html /) }
      it { should contain_file('dir.conf').with_content(/ index\.html\.var /) }
      it { should contain_file('dir.conf').with_content(/ index\.cgi /) }
      it { should contain_file('dir.conf').with_content(/ index\.pl /) }
      it { should contain_file('dir.conf').with_content(/ index\.php /) }
      it { should contain_file('dir.conf').with_content(/ index\.xhtml$/) }
    end
    context "passing indexes => ['example.txt','fearsome.aspx']" do
      let :params do
        {:indexes => ['example.txt','fearsome.aspx']}
      end
      it { should contain_file('dir.conf').with_content(/ example\.txt /) }
      it { should contain_file('dir.conf').with_content(/ fearsome\.aspx$/) }
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
    context "passing no parameters" do
      it { should contain_class("apache::params") }
      it { should contain_apache__mod('dir') }
      it { should contain_file('dir.conf').with_content(/^DirectoryIndex /) }
      it { should contain_file('dir.conf').with_content(/ index\.html /) }
      it { should contain_file('dir.conf').with_content(/ index\.html\.var /) }
      it { should contain_file('dir.conf').with_content(/ index\.cgi /) }
      it { should contain_file('dir.conf').with_content(/ index\.pl /) }
      it { should contain_file('dir.conf').with_content(/ index\.php /) }
      it { should contain_file('dir.conf').with_content(/ index\.xhtml$/) }
    end
    context "passing indexes => ['example.txt','fearsome.aspx']" do
      let :params do
        {:indexes => ['example.txt','fearsome.aspx']}
      end
      it { should contain_file('dir.conf').with_content(/ example\.txt /) }
      it { should contain_file('dir.conf').with_content(/ fearsome\.aspx$/) }
    end
  end
end
