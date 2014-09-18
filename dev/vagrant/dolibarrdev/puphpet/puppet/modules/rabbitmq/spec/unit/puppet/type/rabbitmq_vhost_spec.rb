require 'puppet'
require 'puppet/type/rabbitmq_vhost'
describe Puppet::Type.type(:rabbitmq_vhost) do
  before :each do
    @vhost = Puppet::Type.type(:rabbitmq_vhost).new(:name => 'foo')
  end
  it 'should accept a vhost name' do
    @vhost[:name] = 'dan'
    @vhost[:name].should == 'dan'
  end
  it 'should require a name' do
    expect {
      Puppet::Type.type(:rabbitmq_vhost).new({})
    }.to raise_error(Puppet::Error, 'Title or name must be provided')
  end
  it 'should not allow whitespace in the name' do
    expect {
      @vhost[:name] = 'b r'
    }.to raise_error(Puppet::Error, /Valid values match/)
  end
end
