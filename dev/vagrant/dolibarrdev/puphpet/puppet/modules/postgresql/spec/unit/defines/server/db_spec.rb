require 'spec_helper'

describe 'postgresql::server::db', :type => :define do
  let :facts do
    {
      :osfamily => 'Debian',
      :operatingsystem => 'Debian',
      :operatingsystemrelease => '6.0',
    }
  end

  let :title do
    'test'
  end

  let :params do
    {
      :user => 'test',
      :password => 'test',
      :owner => 'tester',
    }
  end

  it { should contain_postgresql__server__db('test') }
  it { should contain_postgresql__server__database('test').with_owner('tester') }
  it { should contain_postgresql__server__role('test') }
  it { should contain_postgresql__server__database_grant('GRANT test - ALL - test') }
end
