require 'spec_helper'

describe Puppet::Type.type(:vcsrepo).provider(:cvs_provider) do

  let(:resource) { Puppet::Type.type(:vcsrepo).new({
    :name     => 'test',
    :ensure   => :present,
    :provider => :cvs,
    :revision => '2634',
    :source   => 'lp:do',
    :path     => '/tmp/test',
  })}

  let(:provider) { resource.provider }

  before :each do
    Puppet::Util.stubs(:which).with('cvs').returns('/usr/bin/cvs')
  end

  describe 'creating' do
    context "with a source" do
      it "should execute 'cvs checkout'" do
        resource[:source] = ':ext:source@example.com:/foo/bar'
        resource[:revision] = 'an-unimportant-value'
        expects_chdir('/tmp')
        provider.expects(:cvs).with('-d', resource.value(:source), 'checkout', '-r', 'an-unimportant-value', '-d', 'test', 'bar')
        provider.create
      end

      it "should just execute 'cvs checkout' without a revision" do
        resource[:source] = ':ext:source@example.com:/foo/bar'
        resource.delete(:revision)
        provider.expects(:cvs).with('-d', resource.value(:source), 'checkout', '-d', File.basename(resource.value(:path)), File.basename(resource.value(:source)))
        provider.create
      end

      context "with a compression" do
        it "should just execute 'cvs checkout' without a revision" do
          resource[:source] = ':ext:source@example.com:/foo/bar'
          resource[:compression] = '3'
          resource.delete(:revision)
          provider.expects(:cvs).with('-d', resource.value(:source), '-z', '3', 'checkout', '-d', File.basename(resource.value(:path)), File.basename(resource.value(:source)))
          provider.create
        end
      end
    end

    context "when a source is not given" do
      it "should execute 'cvs init'" do
        resource.delete(:source)
        provider.expects(:cvs).with('-d', resource.value(:path), 'init')
        provider.create
      end
    end
  end

  describe 'destroying' do
    it "it should remove the directory" do
      provider.destroy
    end
  end

  describe "checking existence" do
    it "should check for the CVS directory with source" do
      resource[:source] = ':ext:source@example.com:/foo/bar'
      File.expects(:directory?).with(File.join(resource.value(:path), 'CVS'))
      provider.exists?
    end

    it "should check for the CVSROOT directory without source" do
      resource.delete(:source)
      File.expects(:directory?).with(File.join(resource.value(:path), 'CVSROOT'))
      provider.exists?
    end
  end

  describe "checking the revision property" do
    before do
      @tag_file = File.join(resource.value(:path), 'CVS', 'Tag')
    end

    context "when CVS/Tag exists" do
      before do
        @tag = 'TAG'
        File.expects(:exist?).with(@tag_file).returns(true)
      end
      it "should read CVS/Tag" do
        File.expects(:read).with(@tag_file).returns("T#{@tag}")
        provider.revision.should == @tag
      end
    end

    context "when CVS/Tag does not exist" do
      before do
        File.expects(:exist?).with(@tag_file).returns(false)
      end
      it "assumes HEAD" do
        provider.revision.should == 'HEAD'
      end
    end
  end

  describe "when setting the revision property" do
    before do
      @tag = 'SOMETAG'
    end

    it "should use 'cvs update -dr'" do
      expects_chdir
      provider.expects(:cvs).with('update', '-dr', @tag, '.')
      provider.revision = @tag
    end
  end

end
