require 'spec_helper'

describe 'elasticsearch', :type => 'class' do

  context "on an unknown OS" do

    context "it should fail" do
      let :facts do {
        :operatingsystem => 'Windows'
      } end
 
      it { expect { should raise_error(Puppet::Error) } }

    end

  end
 
end
