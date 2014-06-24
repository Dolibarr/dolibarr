require 'spec_helper'

describe 'nginx::package' do

  shared_examples 'redhat' do |operatingsystem|
    let(:facts) {{ :operatingsystem => operatingsystem, :osfamily => 'RedHat' }}

    context "using defaults" do
      it { should contain_package('nginx') }
      it { should contain_yumrepo('nginx-release').with(
        'baseurl'  => 'http://nginx.org/packages/rhel/6/$basearch/',
        'descr'    => 'nginx repo',
        'enabled'  => '1',
        'gpgcheck' => '1',
        'priority' => '1',
        'gpgkey'   => 'http://nginx.org/keys/nginx_signing.key'
      )}
      it { should contain_file('/etc/yum.repos.d/nginx-release.repo') }
      it { should contain_anchor('nginx::package::begin').that_comes_before('Class[nginx::package::redhat]') }
      it { should contain_anchor('nginx::package::end').that_requires('Class[nginx::package::redhat]') }
    end

    context "manage_repo => false" do
      let(:params) {{ :manage_repo => false }}
      it { should contain_package('nginx') }
      it { should_not contain_yumrepo('nginx-release') }
      it { should_not contain_file('/etc/yum.repos.d/nginx-release.repo') }
    end

    context "lsbmajdistrelease = 5" do
      let(:facts) {{ :operatingsystem => operatingsystem, :osfamily => 'RedHat', :lsbmajdistrelease => 5 }}
      it { should contain_package('nginx') }
      it { should contain_yumrepo('nginx-release').with(
        'baseurl'  => 'http://nginx.org/packages/rhel/5/$basearch/'
      )}
      it { should contain_file('/etc/yum.repos.d/nginx-release.repo') }
    end

    describe 'installs the requested package version' do
      let(:facts) {{ :operatingsystem => 'redhat', :osfamily => 'redhat' }}
      let(:params) {{ :package_ensure => '3.0.0' }}

      it 'installs 3.0.0 exactly' do
        should contain_package('nginx').with({
          'ensure' => '3.0.0'
        })
      end
    end
  end

  shared_examples 'debian' do |operatingsystem, lsbdistcodename|
    let(:facts) {{ :operatingsystem => operatingsystem, :osfamily => 'Debian', :lsbdistcodename => lsbdistcodename }}

    context "using defaults" do
      it { should contain_package('nginx') }
      it { should_not contain_package('passenger') }
      it { should contain_apt__source('nginx').with(
        'location'   => "http://nginx.org/packages/#{operatingsystem}",
        'repos'      => 'nginx',
        'key'        => '7BD9BF62',
        'key_source' => 'http://nginx.org/keys/nginx_signing.key'
      )}
      it { should contain_anchor('nginx::package::begin').that_comes_before('Class[nginx::package::debian]') }
      it { should contain_anchor('nginx::package::end').that_requires('Class[nginx::package::debian]') }
    end

    context "package_source => 'passenger'" do
      let(:params) {{ :package_source => 'passenger' }}
      it { should contain_package('nginx') }
      it { should contain_package('passenger') }
      it { should contain_apt__source('nginx').with(
        'location'   => 'https://oss-binaries.phusionpassenger.com/apt/passenger',
        'repos'      => "main",
        'key'        => '561F9B9CAC40B2F7',
        'key_source' => 'https://oss-binaries.phusionpassenger.com/auto-software-signing-gpg-key.txt'
      )}
    end

    context "manage_repo => false" do
      let(:params) {{ :manage_repo => false }}
      it { should contain_package('nginx') }
      it { should_not contain_apt__source('nginx') }
      it { should_not contain_package('passenger') }
    end
  end

  shared_examples 'suse' do |operatingsystem|
    let(:facts) {{ :operatingsystem => operatingsystem, :osfamily => 'Suse'}}
    [
      'nginx-0.8',
      'apache2',
      'apache2-itk',
      'apache2-utils',
      'gd',
      'libapr1',
      'libapr-util1',
      'libjpeg62',
      'libpng14-14',
      'libxslt',
      'rubygem-daemon_controller',
      'rubygem-fastthread',
      'rubygem-file-tail',
      'rubygem-passenger',
      'rubygem-passenger-nginx',
      'rubygem-rack',
      'rubygem-rake',
      'rubygem-spruz',
    ].each do |package|
      it { should contain_package("#{package}") }
    end
    it { should contain_anchor('nginx::package::begin').that_comes_before('Class[nginx::package::suse]') }
    it { should contain_anchor('nginx::package::end').that_requires('Class[nginx::package::suse]') }
  end


  context 'redhat' do
    it_behaves_like 'redhat', 'centos'
    it_behaves_like 'redhat', 'rhel'
    it_behaves_like 'redhat', 'redhat'
    it_behaves_like 'redhat', 'scientific'
    it_behaves_like 'redhat', 'amazon'
  end

  context 'debian' do
    it_behaves_like 'debian', 'debian', 'wheezy'
    it_behaves_like 'debian', 'ubuntu', 'precise'
  end

  context 'suse' do
    it_behaves_like 'suse', 'opensuse'
    it_behaves_like 'suse', 'suse'
  end

  context 'amazon with facter < 1.7.2' do
    let(:facts) {{ :operatingsystem => 'Amazon', :osfamily => 'Linux' }}
      it { should contain_package('nginx') }
      it { should contain_yumrepo('nginx-release').with(
        'baseurl'  => 'http://nginx.org/packages/rhel/6/$basearch/',
        'descr'    => 'nginx repo',
        'enabled'  => '1',
        'gpgcheck' => '1',
        'priority' => '1',
        'gpgkey'   => 'http://nginx.org/keys/nginx_signing.key'
      )}
      it { should contain_file('/etc/yum.repos.d/nginx-release.repo') }
      it { should contain_anchor('nginx::package::begin').that_comes_before('Class[nginx::package::redhat]') }
      it { should contain_anchor('nginx::package::end').that_requires('Class[nginx::package::redhat]') }
  end

  context 'fedora' do
    # fedora is identical to the rest of osfamily RedHat except for not
    # including nginx-release
    let(:facts) {{ :operatingsystem => 'Fedora', :osfamily => 'RedHat', :lsbmajdistrelease => 6 }}
    it { should contain_package('nginx') }
    it { should_not contain_yumrepo('nginx-release') }
    it { should_not contain_file('/etc/yum.repos.d/nginx-release.repo') }
  end

  context 'other' do
    let(:facts) {{ :operatingsystem => 'xxx', :osfamily => 'linux' }}
    it { expect { subject }.to raise_error(Puppet::Error, /Module nginx is not supported on xxx/) }
  end
end
