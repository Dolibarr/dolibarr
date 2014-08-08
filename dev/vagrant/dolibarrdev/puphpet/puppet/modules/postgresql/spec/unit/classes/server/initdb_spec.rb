require 'spec_helper'

describe 'postgresql::server::initdb', :type => :class do
  let (:pre_condition) do
    "include postgresql::server"
  end
  describe 'on RedHat' do
    let :facts do
      {
        :osfamily => 'RedHat',
        :operatingsystem => 'CentOS',
        :operatingsystemrelease => '6.0',
        :concat_basedir => tmpfilename('server'),
      }
    end
    it { should contain_file('/var/lib/pgsql/data').with_ensure('directory') }
  end
  describe 'on Amazon' do
    let :facts do
      {
        :osfamily => 'RedHat',
        :operatingsystem => 'Amazon',
        :concat_basedir => tmpfilename('server'),
      }
    end
    it { should contain_file('/var/lib/pgsql9/data').with_ensure('directory') }
  end
end

