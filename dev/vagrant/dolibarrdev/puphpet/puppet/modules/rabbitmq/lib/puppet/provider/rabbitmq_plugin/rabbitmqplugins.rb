Puppet::Type.type(:rabbitmq_plugin).provide(:rabbitmqplugins) do

  if Puppet::PUPPETVERSION.to_f < 3
    commands :rabbitmqplugins => 'rabbitmq-plugins'
  else
    has_command(:rabbitmqplugins, 'rabbitmq-plugins') do
      environment :HOME => "/tmp"
    end
  end

  defaultfor :feature => :posix

  def self.instances
    rabbitmqplugins('list', '-E').split(/\n/).map do |line|
      if line.split(/\s+/)[1] =~ /^(\S+)$/
        new(:name => $1)
      else
        raise Puppet::Error, "Cannot parse invalid plugins line: #{line}"
      end
    end
  end

  def create
    rabbitmqplugins('enable', resource[:name])
  end

  def destroy
    rabbitmqplugins('disable', resource[:name])
  end

  def exists?
    rabbitmqplugins('list', '-E').split(/\n/).detect do |line|
      line.split(/\s+/)[1].match(/^#{resource[:name]}$/)
    end
  end

end
