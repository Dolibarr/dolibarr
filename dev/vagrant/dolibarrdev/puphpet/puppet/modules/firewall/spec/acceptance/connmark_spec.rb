require 'spec_helper_acceptance'

describe 'firewall type' do

  describe 'connmark' do
    context '50' do
      it 'applies' do
        pp = <<-EOS
          class { '::firewall': }
          firewall { '502 - test':
            proto    => 'all',
	    connmark => '0x1',
            action   => reject,
          }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      it 'should contain the rule' do
        shell('iptables-save') do |r|
          expect(r.stdout).to match(/-A INPUT -m comment --comment "502 - test" -m connmark --mark 0x1 -j REJECT --reject-with icmp-port-unreachable/)
        end
      end
    end
  end
end
