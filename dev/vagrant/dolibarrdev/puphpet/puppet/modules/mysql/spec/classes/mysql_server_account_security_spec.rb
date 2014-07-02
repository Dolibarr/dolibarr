require 'spec_helper'

describe 'mysql::server::account_security' do

  let :facts do {
    :fqdn     => 'myhost.mydomain',
    :hostname => 'myhost',
    :root_home => '/root'
  }
  end

  it 'should remove Mysql_User[root@myhost.mydomain]' do
    should contain_mysql_user('root@myhost.mydomain').with_ensure('absent')
  end
  it 'should remove Mysql_User[root@myhost]' do
    should contain_mysql_user('root@myhost').with_ensure('absent')
  end
  it 'should remove Mysql_User[root@127.0.0.1]' do
    should contain_mysql_user('root@127.0.0.1').with_ensure('absent')
  end
  it 'should remove Mysql_User[root@::1]' do
    should contain_mysql_user('root@::1').with_ensure('absent')
  end
  it 'should remove Mysql_User[@myhost.mydomain]' do
    should contain_mysql_user('@myhost.mydomain').with_ensure('absent')
  end
  it 'should remove Mysql_User[@myhost]' do
    should contain_mysql_user('@myhost').with_ensure('absent')
  end
  it 'should remove Mysql_User[@localhost]' do
    should contain_mysql_user('@localhost').with_ensure('absent')
  end
  it 'should remove Mysql_User[@%]' do
    should contain_mysql_user('@%').with_ensure('absent')
  end

  it 'should remove Mysql_database[test]' do
    should contain_mysql_database('test').with_ensure('absent')
  end

end
