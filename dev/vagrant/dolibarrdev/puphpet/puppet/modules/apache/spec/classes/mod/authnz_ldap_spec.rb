require 'spec_helper'

describe 'apache::mod::authnz_ldap', :type => :class do
  let :pre_condition do
    'include apache'
  end

  context "on a Debian OS" do
    let :facts do
      {
        :lsbdistcodename        => 'squeeze',
        :osfamily               => 'Debian',
        :operatingsystemrelease => '6',
        :concat_basedir         => '/dne',
        :id                     => 'root',
        :kernel                 => 'Linux',
        :operatingsystem        => 'Debian',
        :path                   => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
      }
    end
    it { should contain_class("apache::params") }
    it { should contain_class("apache::mod::ldap") }
    it { should contain_apache__mod('authnz_ldap') }

    context 'default verifyServerCert' do
      it { should contain_file('authnz_ldap.conf').with_content(/^LDAPVerifyServerCert On$/) }
    end

    context 'verifyServerCert = false' do
      let(:params) { { :verifyServerCert => false } }
      it { should contain_file('authnz_ldap.conf').with_content(/^LDAPVerifyServerCert Off$/) }
    end

    context 'verifyServerCert = wrong' do
      let(:params) { { :verifyServerCert => 'wrong' } }
      it 'should raise an error' do
        expect { should raise_error Puppet::Error }
      end
    end
  end #Debian

  context "on a RedHat OS" do
    let :facts do
      {
        :osfamily               => 'RedHat',
        :operatingsystemrelease => '6',
        :concat_basedir         => '/dne',
        :id                     => 'root',
        :kernel                 => 'Linux',
        :operatingsystem        => 'RedHat',
        :path                   => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
      }
    end
    it { should contain_class("apache::params") }
    it { should contain_class("apache::mod::ldap") }
    it { should contain_apache__mod('authnz_ldap') }

    context 'default verifyServerCert' do
      it { should contain_file('authnz_ldap.conf').with_content(/^LDAPVerifyServerCert On$/) }
    end

    context 'verifyServerCert = false' do
      let(:params) { { :verifyServerCert => false } }
      it { should contain_file('authnz_ldap.conf').with_content(/^LDAPVerifyServerCert Off$/) }
    end

    context 'verifyServerCert = wrong' do
      let(:params) { { :verifyServerCert => 'wrong' } }
      it 'should raise an error' do
        expect { should raise_error Puppet::Error }
      end
    end
  end # Redhat

end

