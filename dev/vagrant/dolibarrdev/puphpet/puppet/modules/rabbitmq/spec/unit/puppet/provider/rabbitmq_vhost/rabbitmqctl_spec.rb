require 'puppet'
require 'mocha'
RSpec.configure do |config|
  config.mock_with :mocha
end
provider_class = Puppet::Type.type(:rabbitmq_vhost).provider(:rabbitmqctl)
describe provider_class do
  before :each do
    @resource = Puppet::Type::Rabbitmq_vhost.new(
      {:name => 'foo'}
    )
    @provider = provider_class.new(@resource)
  end
  it 'should match vhost names' do
    @provider.expects(:rabbitmqctl).with('list_vhosts').returns <<-EOT
Listing vhosts ...
foo
...done.
EOT
    @provider.exists?.should == 'foo'
  end
  it 'should not match if no vhosts on system' do
    @provider.expects(:rabbitmqctl).with('list_vhosts').returns <<-EOT
Listing vhosts ...
...done.
EOT
    @provider.exists?.should be_nil
  end
  it 'should not match if no matching vhosts on system' do
    @provider.expects(:rabbitmqctl).with('list_vhosts').returns <<-EOT
Listing vhosts ...
fooey
...done.
EOT
    @provider.exists?.should be_nil
  end
  it 'should call rabbitmqctl to create' do
    @provider.expects(:rabbitmqctl).with('add_vhost', 'foo')
    @provider.create
  end
  it 'should call rabbitmqctl to create' do
    @provider.expects(:rabbitmqctl).with('delete_vhost', 'foo')
    @provider.destroy
  end
end
