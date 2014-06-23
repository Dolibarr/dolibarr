require File.join(File.dirname(__FILE__), '..', 'vcsrepo')

Puppet::Type.type(:vcsrepo).provide(:cvs, :parent => Puppet::Provider::Vcsrepo) do
  desc "Supports CVS repositories/workspaces"

  optional_commands   :cvs => 'cvs'
  has_features :gzip_compression, :reference_tracking, :modules, :cvs_rsh

  def create
    if !@resource.value(:source)
      create_repository(@resource.value(:path))
    else
      checkout_repository
    end
    update_owner
  end

  def exists?
    if @resource.value(:source)
      directory = File.join(@resource.value(:path), 'CVS')
    else
      directory = File.join(@resource.value(:path), 'CVSROOT')
    end
    File.directory?(directory)
  end

  def working_copy_exists?
    File.directory?(File.join(@resource.value(:path), 'CVS'))
  end

  def destroy
    FileUtils.rm_rf(@resource.value(:path))
  end

  def latest?
    debug "Checking for updates because 'ensure => latest'"
    at_path do
      # We cannot use -P to prune empty dirs, otherwise
      # CVS would report those as "missing", regardless
      # if they have contents or updates.
      is_current = (runcvs('-nq', 'update', '-d').strip == "")
      if (!is_current) then debug "There are updates available on the checkout's current branch/tag." end
      return is_current
    end
  end

  def latest
    # CVS does not have a conecpt like commit-IDs or change
    # sets, so we can only have the current branch name (or the
    # requested one, if that differs) as the "latest" revision.
    should = @resource.value(:revision)
    current = self.revision
    return should != current ? should : current
  end

  def revision
    if !@rev
      if File.exist?(tag_file)
        contents = File.read(tag_file).strip
        # Note: Doesn't differentiate between N and T entries
        @rev = contents[1..-1]
      else
        @rev = 'HEAD'
      end
      debug "Checkout is on branch/tag '#{@rev}'"
    end
    return @rev
  end

  def revision=(desired)
    at_path do
      runcvs('update', '-dr', desired, '.')
      update_owner
      @rev = desired
    end
  end

  private

  def tag_file
    File.join(@resource.value(:path), 'CVS', 'Tag')
  end

  def checkout_repository
    dirname, basename = File.split(@resource.value(:path))
    Dir.chdir(dirname) do
      args = ['-d', @resource.value(:source)]
      if @resource.value(:compression)
        args.push('-z', @resource.value(:compression))
      end
      args.push('checkout')
      if @resource.value(:revision)
        args.push('-r', @resource.value(:revision))
      end
      args.push('-d', basename, module_name)
      runcvs(*args)
    end
  end

  # When the source:
  # * Starts with ':' (eg, :pserver:...)
  def module_name
    if (m = @resource.value(:module))
      m
    elsif (source = @resource.value(:source))
      source[0, 1] == ':' ? File.basename(source) : '.'
    end
  end

  def create_repository(path)
    runcvs('-d', path, 'init')
  end

  def update_owner
    if @resource.value(:owner) or @resource.value(:group)
      set_ownership
    end
  end

  def runcvs(*args)
    if @resource.value(:cvs_rsh)
      debug "Using CVS_RSH = " + @resource.value(:cvs_rsh)
      e = { :CVS_RSH => @resource.value(:cvs_rsh) }
    else
      e = {}
    end

    # The location of withenv changed from Puppet 2.x to 3.x
    withenv = Puppet::Util.method(:withenv) if Puppet::Util.respond_to?(:withenv)
    withenv = Puppet::Util::Execution.method(:withenv) if Puppet::Util::Execution.respond_to?(:withenv)
    fail("Cannot set custom environment #{e}") if e && !withenv

    withenv.call e do
      Puppet.debug cvs *args
    end
  end
end
