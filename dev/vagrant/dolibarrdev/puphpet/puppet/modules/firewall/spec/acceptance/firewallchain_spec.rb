require 'spec_helper_acceptance'

describe 'puppet resource firewallchain command:' do
  before :all do
    iptables_flush_all_tables
  end
  describe 'ensure' do
    context 'present' do
      it 'applies cleanly' do
        pp = <<-EOS
          firewallchain { 'MY_CHAIN:filter:IPv4':
            ensure  => present,
          }
        EOS
        # Run it twice and test for idempotency
        apply_manifest(pp, :catch_failures => true)
        apply_manifest(pp, :catch_changes => true)
      end

      it 'finds the chain' do
        shell('iptables-save') do |r|
          expect(r.stdout).to match(/MY_CHAIN/)
        end
      end
    end

    context 'absent' do
      it 'applies cleanly' do
        pp = <<-EOS
          firewallchain { 'MY_CHAIN:filter:IPv4':
            ensure  => absent,
          }
        EOS
        # Run it twice and test for idempotency
        apply_manifest(pp, :catch_failures => true)
        apply_manifest(pp, :catch_changes => true)
      end

      it 'fails to find the chain' do
        shell('iptables-save') do |r|
          expect(r.stdout).to_not match(/MY_CHAIN/)
        end
      end
    end
  end

  # XXX purge => false is not yet implemented
  #context 'adding a firewall rule to a chain:' do
  #  it 'applies cleanly' do
  #    pp = <<-EOS
  #      firewallchain { 'MY_CHAIN:filter:IPv4':
  #        ensure  => present,
  #      }
  #      firewall { '100 my rule':
  #        chain   => 'MY_CHAIN',
  #        action  => 'accept',
  #        proto   => 'tcp',
  #        dport   => 5000,
  #      }
  #    EOS
  #    # Run it twice and test for idempotency
  #    apply_manifest(pp, :catch_failures => true)
  #    apply_manifest(pp, :catch_changes => true)
  #  end
  #end

  #context 'not purge firewallchain chains:' do
  #  it 'does not purge the rule' do
  #    pp = <<-EOS
  #      firewallchain { 'MY_CHAIN:filter:IPv4':
  #        ensure  => present,
  #        purge   => false,
  #        before  => Resources['firewall'],
  #      }
  #      resources { 'firewall':
  #        purge => true,
  #      }
  #    EOS
  #    # Run it twice and test for idempotency
  #    apply_manifest(pp, :catch_failures => true) do |r|
  #      expect(r.stdout).to_not match(/removed/)
  #      expect(r.stderr).to eq('')
  #    end
  #    apply_manifest(pp, :catch_changes => true)
  #  end

  #  it 'still has the rule' do
  #    pp = <<-EOS
  #      firewall { '100 my rule':
  #        chain   => 'MY_CHAIN',
  #        action  => 'accept',
  #        proto   => 'tcp',
  #        dport   => 5000,
  #      }
  #    EOS
  #    # Run it twice and test for idempotency
  #    apply_manifest(pp, :catch_changes => true)
  #  end
  #end

  describe 'policy' do
    after :all do
      shell('iptables -t filter -P FORWARD ACCEPT')
    end

    context 'DROP' do
      it 'applies cleanly' do
        pp = <<-EOS
          firewallchain { 'FORWARD:filter:IPv4':
            policy  => 'drop',
          }
        EOS
        # Run it twice and test for idempotency
        apply_manifest(pp, :catch_failures => true)
        apply_manifest(pp, :catch_changes => true)
      end

      it 'finds the chain' do
        shell('iptables-save') do |r|
          expect(r.stdout).to match(/FORWARD DROP/)
        end
      end
    end
  end
end
