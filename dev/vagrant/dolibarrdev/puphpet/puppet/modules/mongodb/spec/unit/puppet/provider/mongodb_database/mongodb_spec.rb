require 'spec_helper'

describe Puppet::Type.type(:mongodb_database).provider(:mongodb) do

  let(:resource) { Puppet::Type.type(:mongodb_database).new(
    { :ensure   => :present,
      :name     => 'new_database',
      :provider => described_class.name
    }
  )}

  let(:provider) { resource.provider }

  describe 'create' do
    it 'makes a database' do
      provider.expects(:mongo)
      provider.create
    end
  end

  describe 'destroy' do
    it 'removes a database' do
      provider.expects(:mongo)
      provider.destroy
    end
  end

  describe 'exists?' do
    it 'checks if database exists' do
      provider.expects(:mongo).at_least(2).returns("db1,new_database,db2")
      provider.exists?.should be_true
    end
  end

end
