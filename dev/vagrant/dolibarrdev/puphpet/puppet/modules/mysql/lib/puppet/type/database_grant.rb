# This has to be a separate type to enable collecting
Puppet::Type.newtype(:database_grant) do
  @doc = "Manage a database user's rights."
  #ensurable

  autorequire :database do
    # puts "Starting db autoreq for %s" % self[:name]
    reqs = []
    matches = self[:name].match(/^([^@]+)@([^\/]+)\/(.+)$/)
    unless matches.nil?
      reqs << matches[3]
    end
    # puts "Autoreq: '%s'" % reqs.join(" ")
    reqs
  end

  autorequire :database_user do
    # puts "Starting user autoreq for %s" % self[:name]
    reqs = []
    matches = self[:name].match(/^([^@]+)@([^\/]+).*$/)
    unless matches.nil?
      reqs << '%s@%s' % [ matches[1], matches[2] ]
    end
    # puts "Autoreq: '%s'" % reqs.join(" ")
    reqs
  end

  newparam(:name, :namevar=>true) do
    desc 'The primary key: either user@host for global privilges or user@host/database for database specific privileges'
    validate do |value|
      Puppet.warning("database_grant has been deprecated in favor of mysql_grant.")
      true
    end
  end

  newproperty(:privileges, :array_matching => :all) do
    desc 'The privileges the user should have. The possible values are implementation dependent.'

    def should_to_s(newvalue = @should)
      if newvalue
        unless newvalue.is_a?(Array)
          newvalue = [ newvalue ]
        end
        newvalue.collect do |v| v.downcase end.sort.join ', '
      else
        nil
      end
    end

    def is_to_s(currentvalue = @is)
      if currentvalue
        unless currentvalue.is_a?(Array)
          currentvalue = [ currentvalue ]
        end
        currentvalue.collect do |v| v.downcase end.sort.join ', '
      else
        nil
      end
    end

    # use the sorted outputs for comparison
    def insync?(is)
      if defined? @should and @should
        case self.should_to_s
        when 'all'
          self.provider.all_privs_set?
        when self.is_to_s(is)
          true
        else
          false
        end
      else
        true
      end
    end
  end

end

