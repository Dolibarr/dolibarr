Puppet::Type.newtype(:postgresql_psql) do

  newparam(:name) do
    desc "An arbitrary tag for your own reference; the name of the message."
    isnamevar
  end

  newproperty(:command) do
    desc 'The SQL command to execute via psql.'

    defaultto { @resource[:name] }

    def sync(refreshing = false)
      # We're overriding 'sync' here in order to do some magic
      # in support of providing a 'refreshonly' parameter.  This
      # is kind of hacky because the logic for 'refreshonly' is
      # spread between the type and the provider, but this is
      # the least horrible way that I could determine to accomplish
      # it.
      #
      # Note that our overridden version of 'sync' takes a parameter,
      # 'refreshing', which the parent version doesn't take.  This
      # allows us to call the sync method directly from the 'refresh'
      # method, and then inside of the body of 'sync' we can tell
      # whether or not we're refreshing.

      if (!@resource.refreshonly? || refreshing)
        # If we're not in 'refreshonly' mode, or we're not currently
        # refreshing, then we just call the parent method.
        super()
      else
        # If we get here, it means we're in 'refreshonly' mode and
        # we're not being called by the 'refresh' method, so we
        # just no-op.  We'll be called again by the 'refresh'
        # method momentarily.
        nil
      end
    end
  end

  newparam(:unless) do
    desc "An optional SQL command to execute prior to the main :command; " +
        "this is generally intended to be used for idempotency, to check " +
        "for the existence of an object in the database to determine whether " +
        "or not the main SQL command needs to be executed at all."
  end

  newparam(:db) do
    desc "The name of the database to execute the SQL command against."
  end

  newparam(:search_path) do
    desc "The schema search path to use when executing the SQL command"
  end

  newparam(:psql_path) do
    desc "The path to psql executable."
    defaultto("psql")
  end

  newparam(:psql_user) do
    desc "The system user account under which the psql command should be executed."
    defaultto("postgres")
  end

  newparam(:psql_group) do
    desc "The system user group account under which the psql command should be executed."
    defaultto("postgres")
  end

  newparam(:cwd, :parent => Puppet::Parameter::Path) do
    desc "The working directory under which the psql command should be executed."
    defaultto("/tmp")
  end

  newparam(:refreshonly, :boolean => true) do
    desc "If 'true', then the SQL will only be executed via a notify/subscribe event."

    defaultto(:false)
    newvalues(:true, :false)
  end

  def refresh()
    # All of the magic for this type is attached to the ':command' property, so
    # we just need to sync it to accomplish a 'refresh'.
    self.property(:command).sync(true)
  end

end
