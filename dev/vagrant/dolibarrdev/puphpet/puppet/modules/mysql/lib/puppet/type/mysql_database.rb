Puppet::Type.newtype(:mysql_database) do
  @doc = 'Manage MySQL databases.'

  ensurable

  newparam(:name, :namevar => true) do
    desc 'The name of the MySQL database to manage.'
  end

  newproperty(:charset) do
    desc 'The CHARACTER SET setting for the database'
    defaultto :utf8
    newvalue(/^\S+$/)
  end

  newproperty(:collate) do
    desc 'The COLLATE setting for the database'
    defaultto :utf8_general_ci
    newvalue(/^\S+$/)
  end

end
