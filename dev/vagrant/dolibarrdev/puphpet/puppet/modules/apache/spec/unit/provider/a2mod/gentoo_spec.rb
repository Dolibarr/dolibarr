#!/usr/bin/env rspec

require 'spec_helper'

provider_class = Puppet::Type.type(:a2mod).provider(:gentoo)

describe provider_class do
  before :each do
    provider_class.clear
  end

  [:conf_file, :instances, :modules, :initvars, :conf_file, :clear].each do |method|
    it "should respond to the class method #{method}" do
      provider_class.should respond_to(method)
    end
  end

  describe "when fetching modules" do
    before do
      @filetype = mock()
    end

    it "should return a sorted array of the defined parameters" do
      @filetype.expects(:read).returns(%Q{APACHE2_OPTS="-D FOO -D BAR -D BAZ"\n})
      provider_class.expects(:filetype).returns(@filetype)

      provider_class.modules.should == %w{bar baz foo}
    end

    it "should cache the module list" do
      @filetype.expects(:read).once.returns(%Q{APACHE2_OPTS="-D FOO -D BAR -D BAZ"\n})
      provider_class.expects(:filetype).once.returns(@filetype)

      2.times { provider_class.modules.should == %w{bar baz foo} }
    end

    it "should normalize parameters" do
      @filetype.expects(:read).returns(%Q{APACHE2_OPTS="-D FOO -D BAR -D BAR"\n})
      provider_class.expects(:filetype).returns(@filetype)

      provider_class.modules.should == %w{bar foo}
    end
  end

  describe "when prefetching" do
    it "should match providers to resources" do
      provider = mock("ssl_provider", :name => "ssl")
      resource = mock("ssl_resource")
      resource.expects(:provider=).with(provider)

      provider_class.expects(:instances).returns([provider])
      provider_class.prefetch("ssl" => resource)
    end
  end

  describe "when flushing" do
    before :each do
      @filetype = mock()
      @filetype.stubs(:backup)
      provider_class.expects(:filetype).at_least_once.returns(@filetype)

      @info = mock()
      @info.stubs(:[]).with(:name).returns("info")
      @info.stubs(:provider=)

      @mpm = mock()
      @mpm.stubs(:[]).with(:name).returns("mpm")
      @mpm.stubs(:provider=)

      @ssl = mock()
      @ssl.stubs(:[]).with(:name).returns("ssl")
      @ssl.stubs(:provider=)
    end

    it "should add modules whose ensure is present" do
      @filetype.expects(:read).at_least_once.returns(%Q{APACHE2_OPTS=""})
      @filetype.expects(:write).with(%Q{APACHE2_OPTS="-D INFO"})

      @info.stubs(:should).with(:ensure).returns(:present)
      provider_class.prefetch("info" => @info)

      provider_class.flush
    end

    it "should remove modules whose ensure is present" do
      @filetype.expects(:read).at_least_once.returns(%Q{APACHE2_OPTS="-D INFO"})
      @filetype.expects(:write).with(%Q{APACHE2_OPTS=""})

      @info.stubs(:should).with(:ensure).returns(:absent)
      @info.stubs(:provider=)
      provider_class.prefetch("info" => @info)

      provider_class.flush
    end

    it "should not modify providers without resources" do
      @filetype.expects(:read).at_least_once.returns(%Q{APACHE2_OPTS="-D INFO -D MPM"})
      @filetype.expects(:write).with(%Q{APACHE2_OPTS="-D MPM -D SSL"})

      @info.stubs(:should).with(:ensure).returns(:absent)
      provider_class.prefetch("info" => @info)

      @ssl.stubs(:should).with(:ensure).returns(:present)
      provider_class.prefetch("ssl" => @ssl)

      provider_class.flush
    end

    it "should write the modules in sorted order" do
      @filetype.expects(:read).at_least_once.returns(%Q{APACHE2_OPTS=""})
      @filetype.expects(:write).with(%Q{APACHE2_OPTS="-D INFO -D MPM -D SSL"})

      @mpm.stubs(:should).with(:ensure).returns(:present)
      provider_class.prefetch("mpm" => @mpm)
      @info.stubs(:should).with(:ensure).returns(:present)
      provider_class.prefetch("info" => @info)
      @ssl.stubs(:should).with(:ensure).returns(:present)
      provider_class.prefetch("ssl" => @ssl)

      provider_class.flush
    end

    it "should write the records back once" do
      @filetype.expects(:read).at_least_once.returns(%Q{APACHE2_OPTS=""})
      @filetype.expects(:write).once.with(%Q{APACHE2_OPTS="-D INFO -D SSL"})

      @info.stubs(:should).with(:ensure).returns(:present)
      provider_class.prefetch("info" => @info)

      @ssl.stubs(:should).with(:ensure).returns(:present)
      provider_class.prefetch("ssl" => @ssl)

      provider_class.flush
    end

    it "should only modify the line containing APACHE2_OPTS" do
      @filetype.expects(:read).at_least_once.returns(%Q{# Comment\nAPACHE2_OPTS=""\n# Another comment})
      @filetype.expects(:write).once.with(%Q{# Comment\nAPACHE2_OPTS="-D INFO"\n# Another comment})

      @info.stubs(:should).with(:ensure).returns(:present)
      provider_class.prefetch("info" => @info)
      provider_class.flush
    end

    it "should restore any arbitrary arguments" do
      @filetype.expects(:read).at_least_once.returns(%Q{APACHE2_OPTS="-Y -D MPM -X"})
      @filetype.expects(:write).once.with(%Q{APACHE2_OPTS="-Y -X -D INFO -D MPM"})

      @info.stubs(:should).with(:ensure).returns(:present)
      provider_class.prefetch("info" => @info)
      provider_class.flush
    end

    it "should backup the file once if changes were made" do
      @filetype.expects(:read).at_least_once.returns(%Q{APACHE2_OPTS=""})
      @filetype.expects(:write).once.with(%Q{APACHE2_OPTS="-D INFO -D SSL"})

      @info.stubs(:should).with(:ensure).returns(:present)
      provider_class.prefetch("info" => @info)

      @ssl.stubs(:should).with(:ensure).returns(:present)
      provider_class.prefetch("ssl" => @ssl)

      @filetype.unstub(:backup)
      @filetype.expects(:backup)
      provider_class.flush
    end

    it "should not write the file or run backups if no changes were made" do
      @filetype.expects(:read).at_least_once.returns(%Q{APACHE2_OPTS="-X -D INFO -D SSL -Y"})
      @filetype.expects(:write).never

      @info.stubs(:should).with(:ensure).returns(:present)
      provider_class.prefetch("info" => @info)

      @ssl.stubs(:should).with(:ensure).returns(:present)
      provider_class.prefetch("ssl" => @ssl)

      @filetype.unstub(:backup)
      @filetype.expects(:backup).never
      provider_class.flush
    end
  end
end
