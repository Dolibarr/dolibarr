require 'spec_helper'

describe 'postgresql::server', :type => :class do
  let :facts do
    {
      :osfamily => 'Debian',
      :operatingsystem => 'Debian',
      :operatingsystemrelease => '6.0',
      :concat_basedir => tmpfilename('server'),
      :kernel => 'Linux',
    }
  end

  describe 'with no parameters' do
    it { should contain_class("postgresql::params") }
    it { should contain_class("postgresql::server") }
    it 'should validate connection' do
      should contain_postgresql__validate_db_connection('validate_service_is_running')
    end
  end

  describe 'manage_firewall => true' do
    let(:params) do
      {
        :manage_firewall => true,
        :ensure => true,
      }
    end

    it 'should create firewall rule' do
      should contain_firewall("5432 accept - postgres")
    end
  end

  describe 'ensure => absent' do
    let(:params) do
      {
        :ensure => 'absent',
        :datadir => '/my/path',
        :xlogdir => '/xlog/path',
      }
    end

    it 'should make package purged' do
      should contain_package('postgresql-server').with({
        :ensure => 'purged',
      })
    end

    it 'stop the service' do
      should contain_service('postgresqld').with({
        :ensure => false,
      })
    end

    it 'should remove datadir' do
      should contain_file('/my/path').with({
        :ensure => 'absent',
      })
    end

    it 'should remove xlogdir' do
      should contain_file('/xlog/path').with({
        :ensure => 'absent',
      })
    end
  end

  describe 'package_ensure => absent' do
    let(:params) do
      {
        :package_ensure => 'absent',
      }
    end

    it 'should remove the package' do
      should contain_package('postgresql-server').with({
        :ensure => 'purged',
      })
    end

    it 'should still enable the service' do
      should contain_service('postgresqld').with({
        :ensure => true,
      })
    end
  end

  describe 'needs_initdb => true' do
    let(:params) do
      {
        :needs_initdb => true,
      }
    end

    it 'should contain proper initdb exec' do
      should contain_exec('postgresql_initdb')
    end
  end
end
