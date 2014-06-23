require 'puppet'
require 'puppet/type/mysql_grant'
describe Puppet::Type.type(:mysql_grant) do

  before :each do
    @user = Puppet::Type.type(:mysql_grant).new(:name => 'foo@localhost/*.*', :privileges => ['ALL', 'PROXY'], :table => ['*.*','@'], :user => 'foo@localhost')
  end

  it 'should accept a grant name' do
    @user[:name].should == 'foo@localhost/*.*'
  end
  
  it 'should accept ALL privileges' do
    @user[:privileges] = 'ALL'
    @user[:privileges].should == ['ALL']
  end

  it 'should accept PROXY privilege' do
    @user[:privileges] = 'PROXY'
    @user[:privileges].should == ['PROXY']
  end
  
  it 'should accept a table' do
    @user[:table] = '*.*'
    @user[:table].should == '*.*'
  end
  
  it 'should accept @ for table' do
    @user[:table] = '@'
    @user[:table].should == '@'
  end
  
  it 'should accept a user' do
    @user[:user] = 'foo@localhost'
    @user[:user].should == 'foo@localhost'
  end
  
  it 'should require a name' do
    expect {
      Puppet::Type.type(:mysql_grant).new({})
    }.to raise_error(Puppet::Error, 'Title or name must be provided')
  end

end