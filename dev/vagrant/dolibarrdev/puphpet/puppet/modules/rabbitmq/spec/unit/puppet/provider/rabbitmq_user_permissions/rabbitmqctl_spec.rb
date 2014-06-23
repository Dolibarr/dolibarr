require 'puppet'
require 'mocha'
RSpec.configure do |config|
  config.mock_with :mocha
end
describe 'Puppet::Type.type(:rabbitmq_user_permissions).provider(:rabbitmqctl)' do
  before :each do
    @provider_class = Puppet::Type.type(:rabbitmq_user_permissions).provider(:rabbitmqctl)
    @resource = Puppet::Type::Rabbitmq_user_permissions.new(
      {:name => 'foo@bar'}
    )
    @provider = @provider_class.new(@resource)
  end
  after :each do
    @provider_class.instance_variable_set(:@users, nil)
  end
  it 'should match user permissions from list' do
    @provider.class.expects(:rabbitmqctl).with('list_user_permissions', 'foo').returns <<-EOT
Listing users ...
bar 1 2 3
...done.
EOT
    @provider.exists?.should == {:configure=>"1", :write=>"2", :read=>"3"}
  end
  it 'should match user permissions with empty columns' do
    @provider.class.expects(:rabbitmqctl).with('list_user_permissions', 'foo').returns <<-EOT
Listing users ...
bar			3
...done.
EOT
    @provider.exists?.should == {:configure=>"", :write=>"", :read=>"3"}
  end
  it 'should not match user permissions with more than 3 columns' do
    @provider.class.expects(:rabbitmqctl).with('list_user_permissions', 'foo').returns <<-EOT
Listing users ...
bar 1 2 3 4
...done.
EOT
    expect { @provider.exists? }.to raise_error(Puppet::Error, /cannot parse line from list_user_permissions/)
  end
  it 'should not match an empty list' do
    @provider.class.expects(:rabbitmqctl).with('list_user_permissions', 'foo').returns <<-EOT
Listing users ...
...done.
EOT
    @provider.exists?.should == nil
  end
  it 'should create default permissions' do
    @provider.instance_variable_set(:@should_vhost, "bar")
    @provider.instance_variable_set(:@should_user, "foo")
    @provider.expects(:rabbitmqctl).with('set_permissions', '-p', 'bar', 'foo', "''", "''", "''")
    @provider.create 
  end
  it 'should destroy permissions' do
    @provider.instance_variable_set(:@should_vhost, "bar")
    @provider.instance_variable_set(:@should_user, "foo")
    @provider.expects(:rabbitmqctl).with('clear_permissions', '-p', 'bar', 'foo')
    @provider.destroy 
  end
  {:configure_permission => '1', :write_permission => '2', :read_permission => '3'}.each do |k,v|
    it "should be able to retrieve #{k}" do
      @provider.class.expects(:rabbitmqctl).with('list_user_permissions', 'foo').returns <<-EOT
Listing users ...
bar 1 2 3
...done.
EOT
      @provider.send(k).should == v
    end
  end
  {:configure_permission => '1', :write_permission => '2', :read_permission => '3'}.each do |k,v|
    it "should be able to retrieve #{k} after exists has been called" do
      @provider.class.expects(:rabbitmqctl).with('list_user_permissions', 'foo').returns <<-EOT
Listing users ...
bar 1 2 3
...done.
EOT
      @provider.exists?
      @provider.send(k).should == v
    end
  end
  {:configure_permission => ['foo', '2', '3'],
   :read_permission      => ['1', '2', 'foo'],
   :write_permission     => ['1', 'foo', '3']
  }.each do |perm, columns|
    it "should be able to sync #{perm}" do
      @provider.class.expects(:rabbitmqctl).with('list_user_permissions', 'foo').returns <<-EOT
Listing users ...
bar 1 2 3
...done.
EOT
      @provider.resource[perm] = 'foo'
      @provider.expects(:rabbitmqctl).with('set_permissions', '-p', 'bar', 'foo', *columns)
      @provider.send("#{perm}=".to_sym, 'foo')
    end
  end
  it 'should only call set_permissions once' do
    @provider.class.expects(:rabbitmqctl).with('list_user_permissions', 'foo').returns <<-EOT
Listing users ...
bar 1 2 3
...done.
EOT
    @provider.resource[:configure_permission] = 'foo'
    @provider.resource[:read_permission] = 'foo'
    @provider.expects(:rabbitmqctl).with('set_permissions', '-p', 'bar', 'foo', 'foo', '2', 'foo').once
    @provider.configure_permission='foo'
    @provider.read_permission='foo'
  end
end

