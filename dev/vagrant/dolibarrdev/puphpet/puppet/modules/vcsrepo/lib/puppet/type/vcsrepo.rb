require 'pathname'

Puppet::Type.newtype(:vcsrepo) do
  desc "A local version control repository"

  feature :gzip_compression,
          "The provider supports explicit GZip compression levels"
  feature :basic_auth,
          "The provider supports HTTP Basic Authentication"
  feature :bare_repositories,
          "The provider differentiates between bare repositories
          and those with working copies",
          :methods => [:bare_exists?, :working_copy_exists?]

  feature :filesystem_types,
          "The provider supports different filesystem types"

  feature :reference_tracking,
          "The provider supports tracking revision references that can change
           over time (eg, some VCS tags and branch names)"

  feature :ssh_identity,
          "The provider supports a configurable SSH identity file"

  feature :user,
          "The provider can run as a different user"

  feature :modules,
          "The repository contains modules that can be chosen of"

  feature :multiple_remotes,
          "The repository tracks multiple remote repositories"

  feature :configuration,
          "The configuration directory to use"

  feature :cvs_rsh,
          "The provider understands the CVS_RSH environment variable"

  ensurable do
    attr_accessor :latest

    def insync?(is)
      @should ||= []

      case should
      when :present
        return true unless [:absent, :purged, :held].include?(is)
      when :latest
        if is == :latest
          return true
        else
          return false
        end
      when :bare
        return is == :bare
      end
    end

    newvalue :present do
      notice "Creating repository from present"
      provider.create
    end

    newvalue :bare, :required_features => [:bare_repositories] do
      if !provider.exists?
        provider.create
      end
    end

    newvalue :absent do
      provider.destroy
    end

    newvalue :latest, :required_features => [:reference_tracking] do
      if provider.exists?
        if provider.respond_to?(:update_references)
          provider.update_references
        end
        if provider.respond_to?(:latest?)
            reference = provider.latest || provider.revision
        else
          reference = resource.value(:revision) || provider.revision
        end
        notice "Updating to latest '#{reference}' revision"
        provider.revision = reference
      else
        notice "Creating repository from latest"
        provider.create
      end
    end

    def retrieve
      prov = @resource.provider
      if prov
        if prov.working_copy_exists?
          (@should.include?(:latest) && prov.latest?) ? :latest : :present
        elsif prov.class.feature?(:bare_repositories) and prov.bare_exists?
          :bare
        else
          :absent
        end
      else
        raise Puppet::Error, "Could not find provider"
      end
    end

  end

  newparam :path do
    desc "Absolute path to repository"
    isnamevar
    validate do |value|
      path = Pathname.new(value)
      unless path.absolute?
        raise ArgumentError, "Path must be absolute: #{path}"
      end
    end
  end

  newparam :source do
    desc "The source URI for the repository"
  end

  newparam :fstype, :required_features => [:filesystem_types] do
    desc "Filesystem type"
  end

  newproperty :revision do
    desc "The revision of the repository"
    newvalue(/^\S+$/)
  end

  newparam :owner do
    desc "The user/uid that owns the repository files"
  end

  newparam :group do
    desc "The group/gid that owns the repository files"
  end

  newparam :user do
    desc "The user to run for repository operations"
  end

  newparam :excludes do
    desc "Files to be excluded from the repository"
  end

  newparam :force do
    desc "Force repository creation, destroying any files on the path in the process."
    newvalues(:true, :false)
    defaultto false
  end

  newparam :compression, :required_features => [:gzip_compression] do
    desc "Compression level"
    validate do |amount|
      unless Integer(amount).between?(0, 6)
        raise ArgumentError, "Unsupported compression level: #{amount} (expected 0-6)"
      end
    end
  end

  newparam :basic_auth_username, :required_features => [:basic_auth] do
    desc "HTTP Basic Auth username"
  end

  newparam :basic_auth_password, :required_features => [:basic_auth] do
    desc "HTTP Basic Auth password"
  end

  newparam :identity, :required_features => [:ssh_identity] do
    desc "SSH identity file"
  end

  newparam :module, :required_features => [:modules] do
    desc "The repository module to manage"
  end

  newparam :remote, :required_features => [:multiple_remotes] do
    desc "The remote repository to track"
    defaultto "origin"
  end

  newparam :configuration, :required_features => [:configuration]  do
    desc "The configuration directory to use"
  end

  newparam :cvs_rsh, :required_features => [:cvs_rsh] do
    desc "The value to be used for the CVS_RSH environment variable."
  end

  autorequire(:package) do
    ['git', 'git-core']
  end

end
