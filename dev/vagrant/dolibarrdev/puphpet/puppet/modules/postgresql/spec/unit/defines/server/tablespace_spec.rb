require 'spec_helper'

describe 'postgresql::server::tablespace', :type => :define do
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
      :location => '/srv/data/foo',
    }
  end

  it { should contain_postgresql__server__tablespace('test') }
end
