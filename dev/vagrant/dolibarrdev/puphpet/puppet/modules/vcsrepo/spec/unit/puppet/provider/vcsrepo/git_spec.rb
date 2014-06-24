require 'spec_helper'

describe Puppet::Type.type(:vcsrepo).provider(:git_provider) do

  let(:resource) { Puppet::Type.type(:vcsrepo).new({
    :name     => 'test',
    :ensure   => :present,
    :provider => :git,
    :revision => '2634',
    :source   => 'git@repo',
    :path     => '/tmp/test',
  })}

  let(:provider) { resource.provider }

  before :each do
    Puppet::Util.stubs(:which).with('git').returns('/usr/bin/git')
  end

  context 'creating' do
    context "with a revision that is a remote branch" do
      it "should execute 'git clone' and 'git checkout -b'" do
        resource[:revision] = 'only/remote'
        Dir.expects(:chdir).with('/').at_least_once.yields
        Dir.expects(:chdir).with('/tmp/test').at_least_once.yields
        provider.expects(:git).with('clone', resource.value(:source), resource.value(:path))
        provider.expects(:update_submodules)
        provider.expects(:git).with('branch', '-a').returns(resource.value(:revision))
        provider.expects(:git).with('checkout', '--force', resource.value(:revision))
        provider.create
      end
    end

    context "with a revision that is not a remote branch" do
      it "should execute 'git clone' and 'git reset --hard'" do
        resource[:revision] = 'a-commit-or-tag'
        Dir.expects(:chdir).with('/').at_least_once.yields
        Dir.expects(:chdir).with('/tmp/test').at_least_once.yields
        provider.expects(:git).with('clone', resource.value(:source), resource.value(:path))
        provider.expects(:update_submodules)
        provider.expects(:git).with('branch', '-a').returns(resource.value(:revision))
        provider.expects(:git).with('checkout', '--force', resource.value(:revision))
        provider.create
      end

      it "should execute 'git clone' and submodule commands" do
        resource.delete(:revision)
        provider.expects(:git).with('clone', resource.value(:source), resource.value(:path))
        provider.expects(:update_submodules)
        provider.create
      end
    end

    context "with an ensure of bare" do
      context "with revision" do
        it "should just execute 'git clone --bare'" do
          resource[:ensure] = :bare
          provider.expects(:git).with('clone', '--bare', resource.value(:source), resource.value(:path))
          provider.create
        end
      end
      context "without revision" do
        it "should just execute 'git clone --bare'" do
          resource[:ensure] = :bare
          resource.delete(:revision)
          provider.expects(:git).with('clone', '--bare', resource.value(:source), resource.value(:path))
          provider.create
        end
      end
    end

    context "when a source is not given" do
      context "when the path does not exist" do
        it "should execute 'git init'" do
          resource[:ensure] = :present
          resource.delete(:source)
          expects_mkdir
          expects_chdir
          expects_directory?(false)

          provider.expects(:bare_exists?).returns(false)
          provider.expects(:git).with('init')
          provider.create
        end
      end

      context "when the path is a bare repository" do
        it "should convert it to a working copy" do
          resource[:ensure] = :present
          resource.delete(:source)
          provider.expects(:bare_exists?).returns(true)
          provider.expects(:convert_bare_to_working_copy)
          provider.create
        end
      end

      context "when the path is not empty and not a repository" do
        it "should raise an exception" do
          provider.expects(:path_exists?).returns(true)
          provider.expects(:path_empty?).returns(false)
          proc { provider.create }.should raise_error(Puppet::Error)
        end
      end
    end

    context "when the path does not exist" do
      it "should execute 'git init --bare'" do
        resource[:ensure] = :bare
        resource.delete(:source)
        expects_chdir
        expects_mkdir
        expects_directory?(false)
        provider.expects(:working_copy_exists?).returns(false)
        provider.expects(:git).with('init', '--bare')
        provider.create
      end
    end

    context "when the path is a working copy repository" do
      it "should convert it to a bare repository" do
        resource[:ensure] = :bare
        resource.delete(:source)
        provider.expects(:working_copy_exists?).returns(true)
        provider.expects(:convert_working_copy_to_bare)
        provider.create
      end
    end

    context "when the path is not empty and not a repository" do
      it "should raise an exception" do
        expects_directory?(true)
        provider.expects(:path_empty?).returns(false)
        proc { provider.create }.should raise_error(Puppet::Error)
      end
    end
  end


  context 'destroying' do
    it "it should remove the directory" do
      #expects_rm_rf
      provider.destroy
    end
  end

  context "checking the revision property" do
    before do
      expects_chdir('/tmp/test')
      resource[:revision] = 'currentsha'
      resource.delete(:source)
      provider.expects(:git).with('rev-parse', 'HEAD').returns('currentsha')
    end

    context "when its SHA is not different than the current SHA" do
      it "should return the ref" do
        provider.expects(:git).with('config', 'remote.origin.url').returns('')
        provider.expects(:git).with('fetch', 'origin') # FIXME
        provider.expects(:git).with('fetch', '--tags', 'origin')
        provider.expects(:git).with('rev-parse', '--revs-only', resource.value(:revision)).returns('currentsha')
        provider.expects(:git).with('tag', '-l').returns("Hello")
        provider.revision.should == resource.value(:revision)
      end
    end

    context "when its SHA is different than the current SHA" do
      it "should return the current SHA" do
        provider.expects(:git).with('config', 'remote.origin.url').returns('')
        provider.expects(:git).with('fetch', 'origin') # FIXME
        provider.expects(:git).with('fetch', '--tags', 'origin')
        provider.expects(:git).with('rev-parse', '--revs-only', resource.value(:revision)).returns('othersha')
        provider.expects(:git).with('tag', '-l').returns("Hello")
        provider.revision.should == 'currentsha'
      end
    end

    context "when its a ref to a remote head" do
      it "should return the revision" do
        provider.expects(:git).with('config', 'remote.origin.url').returns('')
        provider.expects(:git).with('fetch', 'origin') # FIXME
        provider.expects(:git).with('fetch', '--tags', 'origin')
        provider.expects(:git).with('tag', '-l').returns("Hello")
        provider.expects(:git).with('rev-parse', '--revs-only', resource.value(:revision)).returns('')
        provider.expects(:git).with('ls-remote', '--heads', '--tags', 'origin', resource.value(:revision)).returns("newsha refs/heads/#{resource.value(:revision)}")
        provider.revision.should == 'currentsha'
      end
    end

    context "when its a ref to non existant remote head" do
      it "should fail" do
        provider.expects(:git).with('config', 'remote.origin.url').returns('')
        provider.expects(:git).with('fetch', 'origin') # FIXME
        provider.expects(:git).with('fetch', '--tags', 'origin')
        provider.expects(:git).with('tag', '-l').returns("Hello")
        provider.expects(:git).with('rev-parse', '--revs-only', resource.value(:revision)).returns('')
        provider.expects(:git).with('ls-remote', '--heads', '--tags', 'origin', resource.value(:revision)).returns('')
        expect { provider.revision }.to raise_error(Puppet::Error, /not a local or remote ref$/)
      end
    end

    context "when the source is modified" do
      it "should update the origin url" do
        resource[:source] = 'git://git@foo.com/bar.git'
        provider.expects(:git).with('config', 'remote.origin.url').returns('old')
        provider.expects(:git).with('config', 'remote.origin.url', 'git://git@foo.com/bar.git')
        provider.expects(:git).with('fetch', 'origin') # FIXME
        provider.expects(:git).with('fetch', '--tags', 'origin')
        provider.expects(:git).with('rev-parse', '--revs-only', resource.value(:revision)).returns('currentsha')
        provider.expects(:git).with('tag', '-l').returns("Hello")
        provider.revision.should == resource.value(:revision)
      end
    end
  end

  context "setting the revision property" do
    before do
      expects_chdir
    end
    context "when it's an existing local branch" do
      it "should use 'git fetch' and 'git reset'" do
        resource[:revision] = 'feature/foo'
        provider.expects(:update_submodules)
        provider.expects(:git).with('branch', '-a').returns(resource.value(:revision))
        provider.expects(:git).with('checkout', '--force', resource.value(:revision))
        provider.expects(:git).with('branch', '-a').returns(resource.value(:revision))
        provider.expects(:git).with('reset', '--hard', "origin/#{resource.value(:revision)}")
        provider.revision = resource.value(:revision)
      end
    end
    context "when it's a remote branch" do
      it "should use 'git fetch' and 'git reset'" do
        resource[:revision] = 'only/remote'
        provider.expects(:update_submodules)
        provider.expects(:git).with('branch', '-a').returns(resource.value(:revision))
        provider.expects(:git).with('checkout', '--force', resource.value(:revision))
        provider.expects(:git).with('branch', '-a').returns(resource.value(:revision))
        provider.expects(:git).with('reset', '--hard', "origin/#{resource.value(:revision)}")
        provider.revision = resource.value(:revision)
      end
    end
    context "when it's a commit or tag" do
      it "should use 'git fetch' and 'git reset'" do
        resource[:revision] = 'a-commit-or-tag'
        provider.expects(:git).with('branch', '-a').returns(fixture(:git_branch_a))
        provider.expects(:git).with('checkout', '--force', resource.value(:revision))
        provider.expects(:git).with('branch', '-a').returns(fixture(:git_branch_a))
        provider.expects(:git).with('branch', '-a').returns(fixture(:git_branch_a))
        provider.expects(:git).with('submodule', 'update', '--init', '--recursive')
        provider.revision = resource.value(:revision)
      end
    end
  end

  context "updating references" do
    it "should use 'git fetch --tags'" do
      resource.delete(:source)
      expects_chdir
      provider.expects(:git).with('config', 'remote.origin.url').returns('')
      provider.expects(:git).with('fetch', 'origin')
      provider.expects(:git).with('fetch', '--tags', 'origin')
      provider.update_references
    end
  end

  context "checking if revision" do
    before do
      expects_chdir
      provider.expects(:git).with('branch', '-a').returns(fixture(:git_branch_a))
    end
    context "is a local branch" do
      context "when it's listed in 'git branch -a'" do
        it "should return true" do
          resource[:revision] = 'feature/foo'
          provider.should be_local_branch_revision
        end
      end
      context "when it's not listed in 'git branch -a'" do
        it "should return false" do
          resource[:revision] = 'feature/notexist'
          provider.should_not be_local_branch_revision
        end
      end
    end
    context "is a remote branch" do
      context "when it's listed in 'git branch -a' with an 'origin/' prefix" do
        it "should return true" do
          resource[:revision] = 'only/remote'
          provider.should be_remote_branch_revision
        end
      end
      context "when it's not listed in 'git branch -a' with an 'origin/' prefix" do
        it "should return false" do
          resource[:revision] = 'only/local'
          provider.should_not be_remote_branch_revision
        end
      end
    end
  end

  describe 'latest?' do
    before do
      expects_chdir('/tmp/test')
    end
    context 'when true' do
      it do
        provider.expects(:revision).returns('testrev')
        provider.expects(:latest).returns('testrev')
        provider.latest?.should be_true
      end
    end
    context 'when false' do
      it do
        provider.expects(:revision).returns('master')
        provider.expects(:latest).returns('testrev')
        provider.latest?.should be_false
      end
    end
  end

  describe 'latest' do
    before do
      provider.expects(:get_revision).returns('master')
      expects_chdir
    end
    context 'on master' do
      it do
        provider.expects(:git).with('branch', '-a').returns(fixture(:git_branch_a))
        provider.latest.should == 'master'
      end
    end
    context 'no branch' do
      it do
        provider.expects(:git).with('branch', '-a').returns(fixture(:git_branch_none))
        provider.latest.should == 'master'
      end
    end
    context 'feature/bar' do
      it do
        provider.expects(:git).with('branch', '-a').returns(fixture(:git_branch_feature_bar))
        provider.latest.should == 'master'
      end
    end
  end

  describe 'convert_working_copy_to_bare' do
    it do
      FileUtils.expects(:mv).returns(true)
      FileUtils.expects(:rm_rf).returns(true)
      FileUtils.expects(:mv).returns(true)

      provider.instance_eval { convert_working_copy_to_bare }
    end
  end

  describe 'convert_bare_to_working_copy' do
    it do
      FileUtils.expects(:mv).returns(true)
      FileUtils.expects(:mkdir).returns(true)
      FileUtils.expects(:mv).returns(true)
      provider.expects(:commits_in?).returns(true)
      # If you forget to stub these out you lose 3 hours of rspec work.
      provider.expects(:reset).with('HEAD').returns(true)
      provider.expects(:git_with_identity).returns(true)
      provider.expects(:update_owner_and_excludes).returns(true)

      provider.instance_eval { convert_bare_to_working_copy }
    end
  end

end
