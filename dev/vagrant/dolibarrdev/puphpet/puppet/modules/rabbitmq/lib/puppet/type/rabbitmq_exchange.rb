Puppet::Type.newtype(:rabbitmq_exchange) do
  desc 'Native type for managing rabbitmq exchanges'

  ensurable do
    defaultto(:present)
    newvalue(:present) do
      provider.create
    end
    newvalue(:absent) do
      provider.destroy
    end
  end

  newparam(:name, :namevar => true) do
    desc 'Name of exchange'
    newvalues(/^\S*@\S+$/)
  end

  newparam(:type) do
    desc 'Exchange type to be set *on creation*'
    newvalues(/^\S+$/)
  end

  newparam(:user) do
    desc 'The user to use to connect to rabbitmq'
    defaultto('guest')
    newvalues(/^\S+$/)
  end

  newparam(:password) do
    desc 'The password to use to connect to rabbitmq'
    defaultto('guest')
    newvalues(/\S+/)
  end

  validate do
    if self[:ensure] == :present and self[:type].nil?
      raise ArgumentError, "must set type when creating exchange for #{self[:name]} whose type is #{self[:type]}"
    end
  end

  autorequire(:rabbitmq_vhost) do
    [self[:name].split('@')[1]]
  end

  autorequire(:rabbitmq_user) do
    [self[:user]]
  end

  autorequire(:rabbitmq_user_permissions) do
    ["#{self[:user]}@#{self[:name].split('@')[1]}"]
  end

end
