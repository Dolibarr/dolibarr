require 'spec_helper'

describe 'rabbitmq_erlang_cookie', :type => :fact do
  before(:each) { Facter.clear }

  it 'works correctly' do
    Facter.fact(:osfamily).stubs(:value).returns('RedHat')
    File.stubs(:exists?).with('/var/lib/rabbitmq/.erlang.cookie').returns(true)
    File.stubs(:read).with('/var/lib/rabbitmq/.erlang.cookie').returns('THISISACOOKIE')
    Facter.fact(:rabbitmq_erlang_cookie).value.should == 'THISISACOOKIE'
  end

  it 'fails if file doesnt exist' do
    Facter.fact(:osfamily).stubs(:value).returns('RedHat')
    File.stubs(:exists?).with('/var/lib/rabbitmq/.erlang.cookie').returns(false)
    Facter.fact(:rabbitmq_erlang_cookie).value.should == nil
  end

end
