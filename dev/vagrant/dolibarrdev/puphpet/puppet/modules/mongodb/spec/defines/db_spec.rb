require 'spec_helper'

describe 'mongodb::db', :type => :define do
  let(:title) { 'testdb' }

  let(:params) {
    { 'user'     => 'testuser',
      'password' => 'testpass',
    }
  }

  it 'should contain mongodb_database with mongodb::server requirement' do
    should contain_mongodb_database('testdb')\
      .with_require('Class[Mongodb::Server]')
  end

  it 'should contain mongodb_user with mongodb_database requirement' do
    should contain_mongodb_user('testuser')\
      .with_require('Mongodb_database[testdb]')
  end

  it 'should contain mongodb_user with proper database name' do
    should contain_mongodb_user('testuser')\
      .with_database('testdb')
  end

  it 'should contain mongodb_user with proper roles' do
    params.merge!({'roles' => ['testrole1', 'testrole2']})
    should contain_mongodb_user('testuser')\
      .with_roles(["testrole1", "testrole2"])
  end

  it 'should prefer password_hash instead of password' do
    params.merge!({'password_hash' => 'securehash'})
    should contain_mongodb_user('testuser')\
      .with_password_hash('securehash')
  end

  it 'should contain mongodb_database with proper tries param' do
    params.merge!({'tries' => 5})
    should contain_mongodb_database('testdb').with_tries(5)
  end
end
