require 'puppet'
require 'puppet/type/mongodb_database'
describe Puppet::Type.type(:mongodb_database) do

  before :each do
    @db = Puppet::Type.type(:mongodb_database).new(:name => 'test')
  end

  it 'should accept a database name' do
    @db[:name].should == 'test'
  end

  it 'should accept a tries parameter' do
    @db[:tries] = 5
    @db[:tries].should == 5
  end

  it 'should require a name' do
    expect {
      Puppet::Type.type(:mongodb_database).new({})
    }.to raise_error(Puppet::Error, 'Title or name must be provided')
  end

end
