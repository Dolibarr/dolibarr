require 'spec_helper_acceptance'

# Here we want to test the the resource commands ability to work with different
# existing ruleset scenarios. This will give the parsing capabilities of the
# code a good work out.
describe 'puppet resource firewall command:' do
  context 'make sure it returns no errors when executed on a clean machine' do
    it do
      shell('puppet resource firewall') do |r|
        r.exit_code.should be_zero
        # don't check stdout, some boxes come with rules, that is normal
        r.stderr.should be_empty
      end
    end
  end

  context 'flush iptables and make sure it returns nothing afterwards' do
    before(:all) do
      iptables_flush_all_tables
    end

    # No rules, means no output thanks. And no errors as well.
    it do
      shell('puppet resource firewall') do |r|
        r.exit_code.should be_zero
        r.stderr.should be_empty
        r.stdout.should == "\n"
      end
    end
  end

  context 'accepts rules without comments' do
    before(:all) do
      iptables_flush_all_tables
      shell('iptables -A INPUT -j ACCEPT -p tcp --dport 80')
    end

    it do
      shell('puppet resource firewall') do |r|
        r.exit_code.should be_zero
        # don't check stdout, testing preexisting rules, output is normal
        r.stderr.should be_empty
      end
    end
  end

  context 'accepts rules with invalid comments' do
    before(:all) do
      iptables_flush_all_tables
      shell('iptables -A INPUT -j ACCEPT -p tcp --dport 80 -m comment --comment "http"')
    end

    it do
      shell('puppet resource firewall') do |r|
        r.exit_code.should be_zero
        # don't check stdout, testing preexisting rules, output is normal
        r.stderr.should be_empty
      end
    end
  end

  context 'accepts rules with negation' do
    before :all do
      iptables_flush_all_tables
      shell('iptables -t nat -A POSTROUTING -s 192.168.122.0/24 ! -d 192.168.122.0/24 -p tcp -j MASQUERADE --to-ports 1024-65535')
      shell('iptables -t nat -A POSTROUTING -s 192.168.122.0/24 ! -d 192.168.122.0/24 -p udp -j MASQUERADE --to-ports 1024-65535')
      shell('iptables -t nat -A POSTROUTING -s 192.168.122.0/24 ! -d 192.168.122.0/24 -j MASQUERADE')
    end

    it do
      shell('puppet resource firewall') do |r|
        r.exit_code.should be_zero
        # don't check stdout, testing preexisting rules, output is normal
        r.stderr.should be_empty
      end
    end
  end

  context 'accepts rules with match extension tcp flag' do
    before :all do
      iptables_flush_all_tables
      shell('iptables -t mangle -A PREROUTING -d 1.2.3.4 -p tcp -m tcp -m multiport --dports 80,443,8140 -j MARK --set-mark 42')
    end

    it do
      shell('puppet resource firewall') do |r|
        r.exit_code.should be_zero
        # don't check stdout, testing preexisting rules, output is normal
        r.stderr.should be_empty
      end
    end
  end
end
