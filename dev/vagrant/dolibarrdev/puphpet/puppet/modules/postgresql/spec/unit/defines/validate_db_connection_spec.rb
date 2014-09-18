require 'spec_helper'

describe 'postgresql::validate_db_connection', :type => :define do
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

  describe 'should work with only default parameters' do
    it { should contain_postgresql__validate_db_connection('test') }
  end

  describe 'should work with all parameters' do
    let :params do
      {
        :database_host => 'test',
        :database_name => 'test',
        :database_password => 'test',
        :database_username => 'test',
        :database_port => 5432,
        :run_as => 'postgresq',
        :sleep => 4,
        :tries => 30,
      }
    end
    it { should contain_postgresql__validate_db_connection('test') }
  end
end
