require 'spec_helper_acceptance'

describe 'firewall type' do

  describe 'connlimit_above' do
    context '10' do
      it 'applies' do
        pp = <<-EOS
          class { '::firewall': }
          firewall { '500 - test':
            proto           => tcp,
	    dport           => '22',
	    connlimit_above => '10',
            action          => reject,
          }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      it 'should contain the rule' do
        shell('iptables-save') do |r|
          #connlimit-saddr is added in Ubuntu 14.04.
          expect(r.stdout).to match(/-A INPUT -p tcp -m multiport --dports 22 -m comment --comment "500 - test" -m connlimit --connlimit-above 10 --connlimit-mask 32 (--connlimit-saddr )?-j REJECT --reject-with icmp-port-unreachable/)
        end
      end
    end
  end

  describe 'connlimit_mask' do
    context '24' do
      it 'applies' do
        pp = <<-EOS
          class { '::firewall': }
          firewall { '501 - test':
            proto           => tcp,
            dport           => '22',
            connlimit_above => '10',
            connlimit_mask  => '24',
            action          => reject,
          }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      it 'should contain the rule' do
        shell('iptables-save') do |r|
          #connlimit-saddr is added in Ubuntu 14.04.
          expect(r.stdout).to match(/-A INPUT -p tcp -m multiport --dports 22 -m comment --comment "501 - test" -m connlimit --connlimit-above 10 --connlimit-mask 24 (--connlimit-saddr )?-j REJECT --reject-with icmp-port-unreachable/)
        end
      end
    end
  end
end
