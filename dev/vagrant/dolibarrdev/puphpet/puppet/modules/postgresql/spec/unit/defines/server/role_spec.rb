require 'spec_helper'

describe 'postgresql::server::role', :type => :define do
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
      :password_hash => 'test',
    }
  end

  it { should contain_postgresql__server__role('test') }
end
