require 'puppet'
Puppet::Type.type(:rabbitmq_exchange).provide(:rabbitmqadmin) do

  commands :rabbitmqctl => '/usr/sbin/rabbitmqctl'
  has_command(:rabbitmqadmin, '/usr/local/bin/rabbitmqadmin') do
    environment( { 'HOME' => '' })
  end
  defaultfor :feature => :posix

  def should_vhost
    if @should_vhost
      @should_vhost
    else
      @should_vhost = resource[:name].split('@')[1]
    end
  end

  def self.all_vhosts
    vhosts = []
    parse_command(rabbitmqctl('list_vhosts')).collect do |vhost|
        vhosts.push(vhost)
    end
    vhosts
  end

  def self.all_exchanges(vhost)
    exchanges = []
    parse_command(rabbitmqctl('list_exchanges', '-p', vhost, 'name', 'type'))
  end

  def self.parse_command(cmd_output)
    # first line is:
    # Listing exchanges/vhosts ...
    # while the last line is
    # ...done.
    #
    cmd_output.split(/\n/)[1..-2]
  end

  def self.instances
    resources = []
    all_vhosts.each do |vhost|
        all_exchanges(vhost).collect do |line|
            name, type = line.split()
            if type.nil?
                # if name is empty, it will wrongly get the type's value.
                # This way type will get the correct value
                type = name
                name = ''
            end
            exchange = {
              :type   => type,
              :ensure => :present,
              :name   => "%s@%s" % [name, vhost],
            }
            resources << new(exchange) if exchange[:type]
        end
    end
    resources
  end

  def self.prefetch(resources)
    packages = instances
    resources.keys.each do |name|
      if provider = packages.find{ |pkg| pkg.name == name }
        resources[name].provider = provider
      end
    end
  end

  def exists?
    @property_hash[:ensure] == :present
  end

  def create
    vhost_opt = should_vhost ? "--vhost=#{should_vhost}" : ''
    name = resource[:name].split('@')[0]
    rabbitmqadmin('declare', 'exchange', vhost_opt, "--user=#{resource[:user]}", "--password=#{resource[:password]}", "name=#{name}", "type=#{resource[:type]}")
    @property_hash[:ensure] = :present
  end

  def destroy
    vhost_opt = should_vhost ? "--vhost=#{should_vhost}" : ''
    name = resource[:name].split('@')[0]
    rabbitmqadmin('delete', 'exchange', vhost_opt, "--user=#{resource[:user]}", "--password=#{resource[:password]}", "name=#{name}")
    @property_hash[:ensure] = :absent
  end

end
