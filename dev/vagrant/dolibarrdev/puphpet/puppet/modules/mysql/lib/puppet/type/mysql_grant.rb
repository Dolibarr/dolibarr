# This has to be a separate type to enable collecting
Puppet::Type.newtype(:mysql_grant) do
  @doc = "Manage a MySQL user's rights."
  ensurable

  autorequire(:file) { '/root/.my.cnf' }

  def initialize(*args)
    super
    # Forcibly munge any privilege with 'ALL' in the array to exist of just
    # 'ALL'.  This can't be done in the munge in the property as that iterates
    # over the array and there's no way to replace the entire array before it's
    # returned to the provider.
    if self[:ensure] == :present and Array(self[:privileges]).count > 1 and self[:privileges].to_s.include?('ALL')
      self[:privileges] = 'ALL'
    end
    # Sort the privileges array in order to ensure the comparision in the provider
    # self.instances method match.  Otherwise this causes it to keep resetting the
    # privileges.
    self[:privileges] = Array(self[:privileges]).map(&:upcase).uniq.reject{|k| k == 'GRANT' or k == 'GRANT OPTION'}.sort!
  end

  validate do
    fail('privileges parameter is required.') if self[:ensure] == :present and self[:privileges].nil?
    fail('table parameter is required.') if self[:ensure] == :present and self[:table].nil?
    fail('user parameter is required.') if self[:ensure] == :present and self[:user].nil?
  end

  newparam(:name, :namevar => true) do
    desc 'Name to describe the grant.'

    munge do |value|
      value.delete("'")
    end
  end

  newproperty(:privileges, :array_matching => :all) do
    desc 'Privileges for user'

    munge do |value|
      value.upcase
    end
  end

  newproperty(:table) do
    desc 'Table to apply privileges to.'

    munge do |value|
      value.delete("`")
    end

    newvalues(/.*\..*/,/@/)
  end

  newproperty(:user) do
    desc 'User to operate on.'
    validate do |value|
      # https://dev.mysql.com/doc/refman/5.1/en/account-names.html
      # Regex should problably be more like this: /^[`'"]?[^`'"]*[`'"]?@[`'"]?[\w%\.]+[`'"]?$/
      raise(ArgumentError, "Invalid user #{value}") unless value =~ /[\w-]*@[\w%\.:]+/
      username = value.split('@')[0]
      if username.size > 16
        raise ArgumentError, 'MySQL usernames are limited to a maximum of 16 characters'
      end
    end
  end

  newproperty(:options, :array_matching => :all) do
    desc 'Options to grant.'
  end

end
