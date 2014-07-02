require 'spec_helper'

describe 'apache::service', :type => :class do
  let :pre_condition do
    'include apache::params'
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
    it { should contain_service("httpd").with(
      'name'      => 'apache2',
      'ensure'    => 'running',
      'enable'    => 'true'
      )
    }

    context "with $service_name => 'foo'" do
      let (:params) {{ :service_name => 'foo' }}
      it { should contain_service("httpd").with(
        'name'      => 'foo'
        )
      }
    end

    context "with $service_enable => true" do
      let (:params) {{ :service_enable => true }}
      it { should contain_service("httpd").with(
        'name'      => 'apache2',
        'ensure'    => 'running',
        'enable'    => 'true'
        )
      }
    end

    context "with $service_enable => false" do
      let (:params) {{ :service_enable => false }}
      it { should contain_service("httpd").with(
        'name'      => 'apache2',
        'ensure'    => 'running',
        'enable'    => 'false'
        )
      }
    end

    context "$service_enable must be a bool" do
      let (:params) {{ :service_enable => 'not-a-boolean' }}

      it 'should fail' do
        expect { subject }.to raise_error(Puppet::Error, /is not a boolean/)
      end
    end

    context "with $service_ensure => 'running'" do
      let (:params) {{ :service_ensure => 'running', }}
      it { should contain_service("httpd").with(
        'ensure'    => 'running',
        'enable'    => 'true'
        )
      }
    end

    context "with $service_ensure => 'stopped'" do
      let (:params) {{ :service_ensure => 'stopped', }}
      it { should contain_service("httpd").with(
        'ensure'    => 'stopped',
        'enable'    => 'true'
        )
      }
    end

    context "with $service_ensure => 'UNDEF'" do
      let (:params) {{ :service_ensure => 'UNDEF' }}
      it { should contain_service("httpd").without_ensure }
    end
  end


  context "on a RedHat 5 OS" do
    let :facts do
      {
        :osfamily               => 'RedHat',
        :operatingsystemrelease => '5',
        :concat_basedir         => '/dne',
        :operatingsystem        => 'RedHat',
        :id                     => 'root',
        :kernel                 => 'Linux',
        :path                   => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
      }
    end
    it { should contain_service("httpd").with(
      'name'      => 'httpd',
      'ensure'    => 'running',
      'enable'    => 'true'
      )
    }
  end

  context "on a FreeBSD 5 OS" do
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
    it { should contain_service("httpd").with(
      'name'      => 'apache22',
      'ensure'    => 'running',
      'enable'    => 'true'
      )
    }
  end
end
