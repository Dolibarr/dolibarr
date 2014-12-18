require 'spec_helper'

describe 'os_maj_version fact' do
  before :each do
    Facter.clear
  end

  context "on 5.9 operatingsystemrelease" do
    it "should have os_maj_version => 5" do
      Facter.fact(:operatingsystemrelease).stubs(:value).returns("5.9")
      Facter.fact(:os_maj_version).value.should == "5"
    end
  end

  context "on 6.4 operatingsystemrelease" do
    it "should have os_maj_version => 6" do
      Facter.fact(:operatingsystemrelease).stubs(:value).returns("6.4")
      Facter.fact(:os_maj_version).value.should == "6"
    end
  end
end
