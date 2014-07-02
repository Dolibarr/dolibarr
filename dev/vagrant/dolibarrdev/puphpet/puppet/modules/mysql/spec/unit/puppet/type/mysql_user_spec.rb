require 'puppet'
require 'puppet/type/mysql_user'
describe Puppet::Type.type(:mysql_user) do

  before :each do
    @user = Puppet::Type.type(:mysql_user).new(:name => 'foo@localhost', :password_hash => 'pass')
  end

  it 'should accept a user name' do
    @user[:name].should == 'foo@localhost'
  end

  it 'should fail with a long user name' do
    expect {
      Puppet::Type.type(:mysql_user).new({:name => '12345678901234567@localhost', :password_hash => 'pass'})
      }.to raise_error /MySQL usernames are limited to a maximum of 16 characters/
  end

  it 'should accept a password' do
    @user[:password_hash] = 'foo'
    @user[:password_hash].should == 'foo'
  end

  it 'should require a name' do
    expect {
      Puppet::Type.type(:mysql_user).new({})
    }.to raise_error(Puppet::Error, 'Title or name must be provided')
  end

end
