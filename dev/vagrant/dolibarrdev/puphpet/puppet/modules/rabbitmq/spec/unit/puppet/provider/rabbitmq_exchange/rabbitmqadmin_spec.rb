require 'puppet'
require 'mocha'
RSpec.configure do |config|
  config.mock_with :mocha
end
provider_class = Puppet::Type.type(:rabbitmq_exchange).provider(:rabbitmqadmin)
describe provider_class do
  before :each do
    @resource = Puppet::Type::Rabbitmq_exchange.new(
      {:name => 'amq.direct@/',
       :type => :topic}
    )
    @provider = provider_class.new(@resource)
  end

  it 'should return instances' do
    provider_class.expects(:rabbitmqctl).with('list_vhosts').returns <<-EOT
Listing vhosts ...
/
...done.
EOT
    provider_class.expects(:rabbitmqctl).with('list_exchanges', '-p', '/', 'name', 'type').returns <<-EOT
Listing exchanges ...
        direct
	amq.direct      direct
	amq.fanout      fanout
	amq.headers     headers
	amq.match       headers
	amq.rabbitmq.log        topic
	amq.rabbitmq.trace      topic
	amq.topic       topic
	...done.
EOT
    instances = provider_class.instances
    instances.size.should == 8
  end

  it 'should call rabbitmqadmin to create' do
    @provider.expects(:rabbitmqadmin).with('declare', 'exchange', '--vhost=/', '--user=guest', '--password=guest', 'name=amq.direct', 'type=topic')
    @provider.create
  end

  it 'should call rabbitmqadmin to destroy' do
    @provider.expects(:rabbitmqadmin).with('delete', 'exchange', '--vhost=/', '--user=guest', '--password=guest', 'name=amq.direct')
    @provider.destroy
  end

  context 'specifying credentials' do
    before :each do
      @resource = Puppet::Type::Rabbitmq_exchange.new(
        {:name => 'amq.direct@/',
        :type => :topic,
        :user => 'colin',
        :password => 'secret',
        }
      )
      @provider = provider_class.new(@resource)
    end

    it 'should call rabbitmqadmin to create' do
      @provider.expects(:rabbitmqadmin).with('declare', 'exchange', '--vhost=/', '--user=colin', '--password=secret', 'name=amq.direct', 'type=topic')
      @provider.create
    end
  end
end
