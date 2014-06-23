#
# Author: Emilien Macchi <emilien.macchi@enovance.com>
#

require 'puppet'
require 'puppet/type/mongodb_replset'
describe Puppet::Type.type(:mongodb_replset) do

  before :each do
    @replset = Puppet::Type.type(:mongodb_replset).new(:name => 'test')
  end

  it 'should accept a replica set name' do
    @replset[:name].should == 'test'
  end

  it 'should accept a members array' do
    @replset[:members] = ['mongo1:27017', 'mongo2:27017']
    @replset[:members].should == ['mongo1:27017', 'mongo2:27017']
  end

  it 'should require a name' do
    expect {
      Puppet::Type.type(:mongodb_replset).new({})
    }.to raise_error(Puppet::Error, 'Title or name must be provided')
  end

end
