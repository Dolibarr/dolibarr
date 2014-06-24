# This has to be a separate type to enable collecting
Puppet::Type.newtype(:mysql_user) do
  @doc = 'Manage a MySQL user. This includes management of users password as well as privileges.'

  ensurable

  newparam(:name, :namevar => true) do
    desc "The name of the user. This uses the 'username@hostname' or username@hostname."
    validate do |value|
      # https://dev.mysql.com/doc/refman/5.1/en/account-names.html
      # Regex should problably be more like this: /^[`'"]?[^`'"]*[`'"]?@[`'"]?[\w%\.]+[`'"]?$/
      raise(ArgumentError, "Invalid database user #{value}") unless value =~ /[\w-]*@[\w%\.:]+/
      username = value.split('@')[0]
      if username.size > 16
        raise ArgumentError, 'MySQL usernames are limited to a maximum of 16 characters'
      end
    end
  end

  newproperty(:password_hash) do
    desc 'The password hash of the user. Use mysql_password() for creating such a hash.'
    newvalue(/\w+/)
  end

  newproperty(:max_user_connections) do
    desc "Max concurrent connections for the user. 0 means no (or global) limit."
    newvalue(/\d+/)
  end

  newproperty(:max_connections_per_hour) do
    desc "Max connections per hour for the user. 0 means no (or global) limit."
    newvalue(/\d+/)
  end

  newproperty(:max_queries_per_hour) do
    desc "Max queries per hour for the user. 0 means no (or global) limit."
    newvalue(/\d+/)
  end

  newproperty(:max_updates_per_hour) do
    desc "Max updates per hour for the user. 0 means no (or global) limit."
    newvalue(/\d+/)
  end

end
