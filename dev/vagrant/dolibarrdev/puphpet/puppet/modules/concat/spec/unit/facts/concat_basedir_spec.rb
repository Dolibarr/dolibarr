require 'spec_helper'

describe 'concat_basedir', :type => :fact do
  before(:each) { Facter.clear }

  context 'Puppet[:vardir] ==' do
    it '/var/lib/puppet' do
      Puppet.stubs(:[]).with(:vardir).returns('/var/lib/puppet')
      Facter.fact(:concat_basedir).value.should == '/var/lib/puppet/concat'
    end

    it '/home/apenny/.puppet/var' do
      Puppet.stubs(:[]).with(:vardir).returns('/home/apenny/.puppet/var')
      Facter.fact(:concat_basedir).value.should == '/home/apenny/.puppet/var/concat'
    end
  end

end
