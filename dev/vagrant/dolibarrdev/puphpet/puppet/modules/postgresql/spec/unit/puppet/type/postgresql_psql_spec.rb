require 'spec_helper'

describe Puppet::Type.type(:postgresql_psql), "when validating attributes" do
  [:name, :unless, :db, :psql_path, :psql_user, :psql_group].each do |attr|
    it "should have a #{attr} parameter" do
      expect(Puppet::Type.type(:postgresql_psql).attrtype(attr)).to eq(:param)
    end
  end

  [:command].each do |attr|
    it "should have a #{attr} property" do
      expect(Puppet::Type.type(:postgresql_psql).attrtype(attr)).to eq(:property)
    end
  end
end

describe Puppet::Type.type(:postgresql_psql), :unless => Puppet.features.microsoft_windows? do
  subject do
    Puppet::Type.type(:postgresql_psql).new({:name => 'rspec'}.merge attributes)
  end

  describe "available attributes" do
    {
      :name        => "rspec",
      :command     => "SELECT stuff",
      :unless      => "SELECT other,stuff",
      :db          => "postgres",
      :psql_path   => "/bin/false",
      :psql_user   => "postgres",
      :psql_group  => "postgres",
      :cwd         => "/var/lib",
      :refreshonly => :true,
      :search_path => [ "schema1", "schema2"]
    }.each do |attr, value|
      context attr do
        let(:attributes) do { attr => value } end
        its([attr]) { should == value }
      end
    end

    context "default values" do
      let(:attributes) do {} end
      its([:psql_path]) { should eq("psql") }
      its([:psql_user]) { should eq("postgres") }
      its([:psql_group]) { should eq("postgres") }
      its([:cwd]) { should eq("/tmp") }
      its(:refreshonly?) { should be_false }
    end
  end

  describe "#refreshonly" do
    [true, :true].each do |refreshonly|
      context "=> #{refreshonly.inspect}" do
        let(:attributes) do { :refreshonly => refreshonly } end
        it "has a value of true" do
          expect(subject.refreshonly?).to be_true
        end
        it "will not enforce command on sync because refresh() will be called" do
          expect(subject.provider).to_not receive(:command=)
          subject.property(:command).sync
        end
      end
    end

    [false, :false].each do |refreshonly|
      context "=> #{refreshonly.inspect}" do
        let(:attributes) do { :refreshonly => refreshonly } end
        it "has a value of false" do
          expect(subject.refreshonly?).to be_false
        end
        it "will enforce command on sync because refresh() will not be called" do
          expect(subject.provider).to receive(:command=)
          subject.property(:command).sync
        end
      end
    end
  end

  ## If we refresh the resource, the command should always be run regardless of
  ## refreshonly
  describe "when responding to refresh" do
    [true, :true, false, :false].each do |refreshonly|
      context "with refreshonly => #{refreshonly.inspect}" do
        let(:attributes) do { :refreshonly => refreshonly } end
        it "will enforce command on sync" do
          expect(subject.provider).to receive(:command=)
          subject.refresh
        end
      end
    end
  end
end
