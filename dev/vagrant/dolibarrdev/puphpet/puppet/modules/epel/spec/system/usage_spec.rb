require 'spec_helper_system'

describe 'standage usage tests:' do
  context 'test epel baseurl and mirrorlist' do
    facts = node.facts
    os_maj_version = facts['operatingsystemrelease'].split('.')[0]
    pp = <<-EOS
      class { 'epel':
        epel_baseurl    => 'http://dl.fedoraproject.org/pub/epel/#{os_maj_version}/x86_64/',
        epel_mirrorlist => 'absent',
      }
    EOS

    context puppet_apply pp do
      its(:stderr) { should be_empty }
      its(:exit_code) { should_not == 1 }
      its(:refresh) { should be_nil }
      its(:stderr) { should be_empty }
      its(:exit_code) { should be_zero }
    end

    # Only test for EPEL's presence if not Fedora
    if facts['operatingsystem'] !~ /Fedora/
      # Test the yum config to ensure mirrorlist was emptied
      context shell '/usr/bin/yum-config-manager epel | egrep "^mirrorlist ="' do
        its(:stdout) { should =~ /mirrorlist =\s+/ }
      end

      # Test the yum config to ensure baseurl was defined
      context shell '/usr/bin/yum-config-manager epel | egrep "^baseurl ="' do
        its(:stdout) { should =~ /baseurl = http:\/\/dl.fedoraproject.org\/pub\/epel\/#{os_maj_version}\/x86_64\// }
      end
    end
  end

  context 'test epel-testing is enabled' do
    facts = node.facts
    pp = <<-EOS
      class { 'epel':
        epel_testing_enabled    => '1',
      }
    EOS

    context puppet_apply pp do
      its(:stderr) { should be_empty }
      its(:exit_code) { should_not == 1 }
      its(:refresh) { should be_nil }
      its(:stderr) { should be_empty }
      its(:exit_code) { should be_zero }
    end

    # Only test for EPEL's presence if not Fedora
    if facts['operatingsystem'] !~ /Fedora/
      # Test the yum config to ensure epel-testing was enabled
      context shell '/usr/bin/yum-config-manager epel-testing | grep -q "enabled = True"' do
        its(:exit_code) { should be_zero }
      end
    end
  end
end
