require 'spec_helper'

describe 'postgresql::server::table_grant', :type => :define do
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
      :privilege => 'ALL',
      :db => 'test',
      :role => 'test',
      :table => 'foo',
    }
  end

  it { should contain_postgresql__server__table_grant('test') }
  it { should contain_postgresql__server__grant('table:test') }
end
