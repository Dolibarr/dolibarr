require 'puppet'
require 'puppet/type/rabbitmq_exchange'
describe Puppet::Type.type(:rabbitmq_exchange) do
  before :each do
    @exchange = Puppet::Type.type(:rabbitmq_exchange).new(
      :name => 'foo@bar',
      :type => :topic
    )
  end
  it 'should accept an exchange name' do
    @exchange[:name] = 'dan@pl'
    @exchange[:name].should == 'dan@pl'
  end
  it 'should require a name' do
    expect {
      Puppet::Type.type(:rabbitmq_exchange).new({})
    }.to raise_error(Puppet::Error, 'Title or name must be provided')
  end
  it 'should not allow whitespace in the name' do
    expect {
      @exchange[:name] = 'b r'
    }.to raise_error(Puppet::Error, /Valid values match/)
  end
  it 'should not allow names without @' do
    expect {
      @exchange[:name] = 'b_r'
    }.to raise_error(Puppet::Error, /Valid values match/)
  end

  it 'should accept an exchange type' do
    @exchange[:type] = :direct
    @exchange[:type].should == :direct
  end
  it 'should require a type' do
    expect {
      Puppet::Type.type(:rabbitmq_exchange).new(:name => 'foo@bar')
    }.to raise_error(/.*must set type when creating exchange.*/)
  end
  it 'should not require a type when destroying' do
    expect {
            Puppet::Type.type(:rabbitmq_exchange).new(:name => 'foo@bar', :ensure => :absent)
    }.to_not raise_error
  end

  it 'should accept a user' do
    @exchange[:user] = :root
    @exchange[:user].should == :root
  end

  it 'should accept a password' do
    @exchange[:password] = :PaSsw0rD
    @exchange[:password].should == :PaSsw0rD
  end
end
