require 'puppet'
require 'puppet/type/rabbitmq_user_permissions'
describe Puppet::Type.type(:rabbitmq_user_permissions) do
  before :each do
    @perms = Puppet::Type.type(:rabbitmq_user_permissions).new(:name => 'foo@bar')
  end
  it 'should accept a valid hostname name' do
    @perms[:name] = 'dan@bar'
    @perms[:name].should == 'dan@bar'
  end
  it 'should require a name' do
    expect {
      Puppet::Type.type(:rabbitmq_user_permissions).new({})
    }.to raise_error(Puppet::Error, 'Title or name must be provided')
  end
  it 'should fail when names dont have a @' do
    expect {
      @perms[:name] = 'bar'
    }.to raise_error(Puppet::Error, /Valid values match/)
  end
  [:configure_permission, :read_permission, :write_permission].each do |param|
    it 'should not default to anything' do
       @perms[param].should == nil
    end
    it "should accept a valid regex for #{param}" do
      @perms[param] = '.*?'
      @perms[param].should == '.*?'  
    end
    it "should accept an empty string for #{param}" do
      @perms[param] = ''
      @perms[param].should == ''  
    end
    it "should not accept invalid regex for #{param}" do
      expect {
        @perms[param] = '*'
      }.to raise_error(Puppet::Error, /Invalid regexp/)
    end
  end
  {:rabbitmq_vhost => 'dan@test', :rabbitmq_user => 'test@dan'}.each do |k,v|
    it "should autorequire #{k}" do
      if k == :rabbitmq_vhost
        vhost = Puppet::Type.type(k).new(:name => "test")
      else
        vhost = Puppet::Type.type(k).new(:name => "test", :password => 'pass')
      end
      perm  = Puppet::Type.type(:rabbitmq_user_permissions).new(:name => v)
      config = Puppet::Resource::Catalog.new :testing do |conf|
        [vhost, perm].each { |resource| conf.add_resource resource }
      end
      rel = perm.autorequire[0]
      rel.source.ref.should == vhost.ref
      rel.target.ref.should == perm.ref
    end
  end
end
