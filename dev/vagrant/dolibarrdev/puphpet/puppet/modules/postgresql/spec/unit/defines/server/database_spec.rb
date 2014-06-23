require 'spec_helper'

describe 'postgresql::server::database', :type => :define do
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
  it { should contain_postgresql__server__database('test') }
  it { should contain_postgresql_psql("Check for existence of db 'test'") }
end
