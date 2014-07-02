Puppet::Type.newtype(:rabbitmq_plugin) do
  desc 'manages rabbitmq plugins'

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
    'name of the plugin to enable'
    newvalues(/^\S+$/)
  end

end
