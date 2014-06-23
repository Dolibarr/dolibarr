require 'spec_helper'

describe "Facter::Util::Fact iptables_persistent_version" do
  before { Facter.clear }
  let(:dpkg_cmd) { "dpkg-query -Wf '${Version}' iptables-persistent 2>/dev/null" }

  {
    "Debian" => "0.0.20090701",
    "Ubuntu" => "0.5.3ubuntu2",
  }.each do |os, ver|
    describe "#{os} package installed" do
      before {
        allow(Facter.fact(:operatingsystem)).to receive(:value).and_return(os)
        allow(Facter::Util::Resolution).to receive(:exec).with(dpkg_cmd).
          and_return(ver)
      }
      it { Facter.fact(:iptables_persistent_version).value.should == ver }
    end
  end

  describe 'Ubuntu package not installed' do
    before {
      allow(Facter.fact(:operatingsystem)).to receive(:value).and_return('Ubuntu')
      allow(Facter::Util::Resolution).to receive(:exec).with(dpkg_cmd).
        and_return(nil)
    }
    it { Facter.fact(:iptables_persistent_version).value.should be_nil }
  end

  describe 'CentOS not supported' do
    before { allow(Facter.fact(:operatingsystem)).to receive(:value).
               and_return("CentOS") }
    it { Facter.fact(:iptables_persistent_version).value.should be_nil }
  end
end
