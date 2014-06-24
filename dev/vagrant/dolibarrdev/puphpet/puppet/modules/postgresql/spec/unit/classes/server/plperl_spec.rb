require 'spec_helper'

describe 'postgresql::server::plperl', :type => :class do
  let :facts do
    {
      :osfamily => 'Debian',
      :operatingsystem => 'Debian',
      :operatingsystemrelease => '6.0',
      :kernel => 'Linux',
      :concat_basedir => tmpfilename('plperl'),
    }
  end

  let :pre_condition do
    "class { 'postgresql::server': }"
  end

  describe 'with no parameters' do
    it { should contain_class("postgresql::server::plperl") }
    it 'should create package' do
      should contain_package('postgresql-plperl').with({
        :ensure => 'present',
        :tag => 'postgresql',
      })
    end
  end

  describe 'with parameters' do
    let :params do
      {
        :package_ensure => 'absent',
        :package_name => 'mypackage',
      }
    end

    it { should contain_class("postgresql::server::plperl") }
    it 'should create package with correct params' do
      should contain_package('postgresql-plperl').with({
        :ensure => 'absent',
        :name => 'mypackage',
        :tag => 'postgresql',
      })
    end
  end
end
