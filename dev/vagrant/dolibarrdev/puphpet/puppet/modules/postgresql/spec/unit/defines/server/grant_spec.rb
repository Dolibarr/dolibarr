require 'spec_helper'

describe 'postgresql::server::grant', :type => :define do
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
      :db => 'test',
      :role => 'test',
    }
  end

  it { should contain_postgresql__server__grant('test') }
end
