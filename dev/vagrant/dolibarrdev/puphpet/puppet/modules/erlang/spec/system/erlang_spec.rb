require 'spec_helper_system'

describe 'The erlang puppet module' do
  it 'should run without errors' do
    pp = <<-EOS
      class { 'erlang':
        epel_enable => true
      }
    EOS

    puppet_apply(pp) do |r|
      r.exit_code.should == 2
      r.refresh
      r.exit_code.should be_zero
    end
  end

  it 'should install the erl binary into /usr/bin' do
    shell 'which erl' do |r|
      r.stdout.should =~ /\/usr\/bin\/erl/
      r.stderr.should be_empty
      r.exit_code.should be_zero
    end
  end
end
