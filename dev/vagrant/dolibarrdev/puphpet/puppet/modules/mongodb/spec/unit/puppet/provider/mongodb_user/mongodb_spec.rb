require 'spec_helper'

describe Puppet::Type.type(:mongodb_user).provider(:mongodb) do

  let(:resource) { Puppet::Type.type(:mongodb_user).new(
    { :ensure        => :present,
      :name          => 'new_user',
      :database      => 'new_database',
      :password_hash => 'pass',
      :roles         => ['role1', 'role2'],
      :provider      => described_class.name
    }
  )}

  let(:provider) { resource.provider }

  describe 'create' do
    it 'creates a user' do
      provider.expects(:mongo)
      provider.create
    end
  end

  describe 'destroy' do
    it 'removes a user' do
      provider.expects(:mongo)
      provider.destroy
    end
  end

  describe 'exists?' do
    it 'checks if user exists' do
      provider.expects(:mongo).at_least(2).returns("1")
      provider.exists?.should be_true
    end
  end

  describe 'password_hash' do
    it 'returns a password_hash' do
      provider.expects(:mongo).returns("pass\n")
      provider.password_hash.should == "pass"
    end
  end

  describe 'password_hash=' do
    it 'changes a password_hash' do
      provider.expects(:mongo)
      provider.password_hash=("newpass")
    end
  end

  describe 'roles' do
    it 'returns a sorted roles' do
      provider.expects(:mongo).returns("role2,role1\n")
      provider.roles.should == ['role1','role2']
    end
  end

  describe 'roles=' do
    it 'changes a roles' do
      provider.expects(:mongo)
      provider.roles=(['role3','role4'])
    end
  end

end
