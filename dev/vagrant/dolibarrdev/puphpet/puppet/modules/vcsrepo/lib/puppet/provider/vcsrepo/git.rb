require File.join(File.dirname(__FILE__), '..', 'vcsrepo')

Puppet::Type.type(:vcsrepo).provide(:git, :parent => Puppet::Provider::Vcsrepo) do
  desc "Supports Git repositories"

  ##TODO modify the commands below so that the su - is included
  optional_commands :git => 'git',
                    :su  => 'su'
  has_features :bare_repositories, :reference_tracking, :ssh_identity, :multiple_remotes, :user

  def create
    if !@resource.value(:source)
      init_repository(@resource.value(:path))
    else
      clone_repository(@resource.value(:source), @resource.value(:path))
      if @resource.value(:revision)
        if @resource.value(:ensure) == :bare
          notice "Ignoring revision for bare repository"
        else
          checkout
        end
      end
      if @resource.value(:ensure) != :bare
        update_submodules
      end
    end
    update_owner_and_excludes
  end

  def destroy
    FileUtils.rm_rf(@resource.value(:path))
  end

  def latest?
    at_path do
      return self.revision == self.latest
    end
  end

  def latest
    branch = on_branch?
    if branch == 'master'
      return get_revision("#{@resource.value(:remote)}/HEAD")
    elsif branch == '(no branch)'
      return get_revision('HEAD')
    else
      return get_revision("#{@resource.value(:remote)}/%s" % branch)
    end
  end

  def revision
    update_references
    current = at_path { git_with_identity('rev-parse', 'HEAD').chomp }
    return current unless @resource.value(:revision)

    if tag_revision?(@resource.value(:revision))
      canonical = at_path { git_with_identity('show', @resource.value(:revision)).scan(/^commit (.*)/).to_s }
    else
      # if it's not a tag, look for it as a local ref
      canonical = at_path { git_with_identity('rev-parse', '--revs-only', @resource.value(:revision)).chomp }
      if canonical.empty?
        # git rev-parse executed properly but didn't find the ref;
        # look for it in the remote
        remote_ref = at_path { git_with_identity('ls-remote', '--heads', '--tags', @resource.value(:remote), @resource.value(:revision)).chomp }
        if remote_ref.empty?
          fail("#{@resource.value(:revision)} is not a local or remote ref")
        end

        # $ git ls-remote --heads --tags origin feature/cvs
        # 7d4244b35e72904e30130cad6d2258f901c16f1a	refs/heads/feature/cvs
        canonical = remote_ref.split.first
      end
    end

    if current == canonical
      @resource.value(:revision)
    else
      current
    end
  end

  def revision=(desired)
    checkout(desired)
    if local_branch_revision?(desired)
      # reset instead of pull to avoid merge conflicts. assuming remote is
      # authoritative.
      # might be worthwhile to have an allow_local_changes param to decide
      # whether to reset or pull when we're ensuring latest.
      at_path { git_with_identity('reset', '--hard', "#{@resource.value(:remote)}/#{desired}") }
    end
    if @resource.value(:ensure) != :bare
      update_submodules
    end
    update_owner_and_excludes
  end

  def bare_exists?
    bare_git_config_exists? && !working_copy_exists?
  end

  def working_copy_exists?
    File.directory?(File.join(@resource.value(:path), '.git'))
  end

  def exists?
    working_copy_exists? || bare_exists?
  end

  def update_remote_origin_url
    current = git_with_identity('config', 'remote.origin.url')
    unless @resource.value(:source).nil?
      if current.nil? or current.strip != @resource.value(:source)
        git_with_identity('config', 'remote.origin.url', @resource.value(:source))
      end
    end
  end

  def update_references
    at_path do
      update_remote_origin_url
      git_with_identity('fetch', @resource.value(:remote))
      git_with_identity('fetch', '--tags', @resource.value(:remote))
      update_owner_and_excludes
    end
  end

  private

  def bare_git_config_exists?
    File.exist?(File.join(@resource.value(:path), 'config'))
  end

  def clone_repository(source, path)
    check_force
    args = ['clone']
    if @resource.value(:ensure) == :bare
      args << '--bare'
    end
    if !File.exist?(File.join(@resource.value(:path), '.git'))
      args.push(source, path)
      Dir.chdir("/") do
        git_with_identity(*args)
      end
    else
      notice "Repo has already been cloned"
    end
  end

  def check_force
    if path_exists? and not path_empty?
      if @resource.value(:force)
        notice "Removing %s to replace with vcsrepo." % @resource.value(:path)
        destroy
      else
        raise Puppet::Error, "Could not create repository (non-repository at path)"
      end
    end
  end

  def init_repository(path)
    check_force
    if @resource.value(:ensure) == :bare && working_copy_exists?
      convert_working_copy_to_bare
    elsif @resource.value(:ensure) == :present && bare_exists?
      convert_bare_to_working_copy
    else
      # normal init
      FileUtils.mkdir(@resource.value(:path))
      FileUtils.chown(@resource.value(:user), nil, @resource.value(:path)) if @resource.value(:user)
      args = ['init']
      if @resource.value(:ensure) == :bare
        args << '--bare'
      end
      at_path do
        git_with_identity(*args)
      end
    end
  end

  # Convert working copy to bare
  #
  # Moves:
  #   <path>/.git
  # to:
  #   <path>/
  def convert_working_copy_to_bare
    notice "Converting working copy repository to bare repository"
    FileUtils.mv(File.join(@resource.value(:path), '.git'), tempdir)
    FileUtils.rm_rf(@resource.value(:path))
    FileUtils.mv(tempdir, @resource.value(:path))
  end

  # Convert bare to working copy
  #
  # Moves:
  #   <path>/
  # to:
  #   <path>/.git
  def convert_bare_to_working_copy
    notice "Converting bare repository to working copy repository"
    FileUtils.mv(@resource.value(:path), tempdir)
    FileUtils.mkdir(@resource.value(:path))
    FileUtils.mv(tempdir, File.join(@resource.value(:path), '.git'))
    if commits_in?(File.join(@resource.value(:path), '.git'))
      reset('HEAD')
      git_with_identity('checkout', '--force')
      update_owner_and_excludes
    end
  end

  def commits_in?(dot_git)
    Dir.glob(File.join(dot_git, 'objects/info/*'), File::FNM_DOTMATCH) do |e|
      return true unless %w(. ..).include?(File::basename(e))
    end
    false
  end

  def checkout(revision = @resource.value(:revision))
    if !local_branch_revision? && remote_branch_revision?
      at_path { git_with_identity('checkout', '-b', revision, '--track', "#{@resource.value(:remote)}/#{revision}") }
    else
      at_path { git_with_identity('checkout', '--force', revision) }
    end
  end

  def reset(desired)
    at_path do
      git_with_identity('reset', '--hard', desired)
    end
  end

  def update_submodules
    at_path do
      git_with_identity('submodule', 'update', '--init', '--recursive')
    end
  end

  def remote_branch_revision?(revision = @resource.value(:revision))
    # git < 1.6 returns '#{@resource.value(:remote)}/#{revision}'
    # git 1.6+ returns 'remotes/#{@resource.value(:remote)}/#{revision}'
    branch = at_path { branches.grep /(remotes\/)?#{@resource.value(:remote)}\/#{revision}/ }
    branch unless branch.empty?
  end

  def local_branch_revision?(revision = @resource.value(:revision))
    at_path { branches.include?(revision) }
  end

  def tag_revision?(revision = @resource.value(:revision))
    at_path { tags.include?(revision) }
  end

  def branches
    at_path { git_with_identity('branch', '-a') }.gsub('*', ' ').split(/\n/).map { |line| line.strip }
  end

  def on_branch?
    at_path { git_with_identity('branch', '-a') }.split(/\n/).grep(/\*/).first.to_s.gsub('*', '').strip
  end

  def tags
    at_path { git_with_identity('tag', '-l') }.split(/\n/).map { |line| line.strip }
  end

  def set_excludes
    at_path { open('.git/info/exclude', 'w') { |f| @resource.value(:excludes).each { |ex| f.write(ex + "\n") }}}
  end

  def get_revision(rev)
    if !working_copy_exists?
      create
    end
    at_path do
      update_remote_origin_url
      git_with_identity('fetch', @resource.value(:remote))
      git_with_identity('fetch', '--tags', @resource.value(:remote))
    end
    current = at_path { git_with_identity('rev-parse', rev).strip }
    if @resource.value(:revision)
      if local_branch_revision?
        canonical = at_path { git_with_identity('rev-parse', @resource.value(:revision)).strip }
      elsif remote_branch_revision?
        canonical = at_path { git_with_identity('rev-parse', "#{@resource.value(:remote)}/" + @resource.value(:revision)).strip }
      end
      current = @resource.value(:revision) if current == canonical
    end
    update_owner_and_excludes
    return current
  end

  def update_owner_and_excludes
    if @resource.value(:owner) or @resource.value(:group)
      set_ownership
    end
    if @resource.value(:excludes)
      set_excludes
    end
  end

  def git_with_identity(*args)
    if @resource.value(:identity)
      Tempfile.open('git-helper') do |f|
        f.puts '#!/bin/sh'
        f.puts "exec ssh -oStrictHostKeyChecking=no -oPasswordAuthentication=no -oKbdInteractiveAuthentication=no -oChallengeResponseAuthentication=no -oConnectTimeout=120 -i #{@resource.value(:identity)} $*"
        f.close

        FileUtils.chmod(0755, f.path)
        env_save = ENV['GIT_SSH']
        ENV['GIT_SSH'] = f.path

        ret = git(*args)

        ENV['GIT_SSH'] = env_save

        return ret
      end
    elsif @resource.value(:user)
      su(@resource.value(:user), '-c', "git #{args.join(' ')}" )
    else
      git(*args)
    end
  end
end
