require 'spec_helper'

describe 'mysql::db', :type => :define do
  let(:facts) {{ :osfamily => 'RedHat' }}
  let(:title) { 'test_db' }

  let(:params) {
    { 'user'     => 'testuser',
      'password' => 'testpass',
    }
  }

  it 'should report an error when ensure is not present or absent' do
    params.merge!({'ensure' => 'invalid_val'})
    expect { subject }.to raise_error(Puppet::Error,
      /invalid_val is not supported for ensure\. Allowed values are 'present' and 'absent'\./)
  end

  it 'should not notify the import sql exec if no sql script was provided' do
    should contain_mysql_database('test_db').without_notify
  end

  it 'should subscribe to database if sql script is given' do
    params.merge!({'sql' => 'test_sql'})
    should contain_exec('test_db-import').with_subscribe('Mysql_database[test_db]')
  end

  it 'should only import sql script on creation if not enforcing' do
    params.merge!({'sql' => 'test_sql', 'enforce_sql' => false})
    should contain_exec('test_db-import').with_refreshonly(true)
  end

  it 'should import sql script on creation if enforcing' do
    params.merge!({'sql' => 'test_sql', 'enforce_sql' => true})
    should contain_exec('test_db-import').with_refreshonly(false)
  end

  it 'should not create database and database user' do
    params.merge!({'ensure' => 'absent', 'host' => 'localhost'})
    should contain_mysql_database('test_db').with_ensure('absent')
    should contain_mysql_user('testuser@localhost').with_ensure('absent')
  end

  it 'should create with an appropriate collate and charset' do
    params.merge!({'charset' => 'utf8', 'collate' => 'utf8_danish_ci'})
    should contain_mysql_database('test_db').with({
      'charset' => 'utf8',
      'collate' => 'utf8_danish_ci',
    })
  end
end
