require 'spec_helper_system'

describe 'epel class:' do
  context puppet_agent do
    its(:stderr) { should be_empty }
    its(:exit_code) { should_not == 1 }
  end

  # Verify the os_maj_version fact is working
  context shell 'facter --puppet os_maj_version' do
    its(:stdout) { should_not be_empty }
    its(:stderr) { should be_empty }
    its(:exit_code) { should be_zero }
  end

  pp = "class { 'epel': }"

  context puppet_apply pp do
    its(:stderr) { should be_empty }
    its(:exit_code) { should_not == 1 }
    its(:refresh) { should be_nil }
    its(:stderr) { should be_empty }
    its(:exit_code) { should be_zero }
  end

  context 'test EPEL repo presence' do
    facts = node.facts

    # Only test for EPEL's presence if not Fedora
    if facts['operatingsystem'] !~ /Fedora/
      context shell '/usr/bin/yum-config-manager epel | grep -q "\[epel\]"' do
        its(:exit_code) { should be_zero }
      end
    end
  end
end
