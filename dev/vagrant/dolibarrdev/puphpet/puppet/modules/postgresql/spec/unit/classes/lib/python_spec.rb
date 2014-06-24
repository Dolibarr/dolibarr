require 'spec_helper'

describe 'postgresql::lib::python', :type => :class do

  describe 'on a redhat based os' do
    let :facts do {
      :osfamily => 'RedHat',
      :operatingsystem => 'RedHat',
      :operatingsystemrelease => '6.4',
    }
    end
    it { should contain_package('python-psycopg2').with(
      :name => 'python-psycopg2',
      :ensure => 'present'
    )}
  end

  describe 'on a debian based os' do
    let :facts do {
      :osfamily => 'Debian',
      :operatingsystem => 'Debian',
      :operatingsystemrelease => '6.0',
    }
    end
    it { should contain_package('python-psycopg2').with(
      :name => 'python-psycopg2',
      :ensure => 'present'
    )}
  end

end
