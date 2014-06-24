require 'spec_helper'

describe 'sysctl::base', :type => :class do

  it { should create_class('sysctl::base') }
  it { should contain_file('/etc/sysctl.d') }

end

