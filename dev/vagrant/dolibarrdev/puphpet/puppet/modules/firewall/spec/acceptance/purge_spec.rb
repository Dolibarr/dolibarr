require 'spec_helper_acceptance'

describe "purge tests:" do
  context('resources purge') do
    before(:all) do
      iptables_flush_all_tables

      shell('iptables -A INPUT -s 1.2.1.2')
      shell('iptables -A INPUT -s 1.2.1.2')
    end

    it 'make sure duplicate existing rules get purged' do

      pp = <<-EOS
        class { 'firewall': }
        resources { 'firewall':
          purge => true,
        }
      EOS

      apply_manifest(pp, :expect_changes => true)
    end

    it 'saves' do
      shell('iptables-save') do |r|
        expect(r.stdout).to_not match(/1\.2\.1\.2/)
        expect(r.stderr).to eq("")
      end
    end
  end

  context('chain purge') do
    before(:each) do
      iptables_flush_all_tables

      shell('iptables -A INPUT -p tcp -s 1.2.1.1')
      shell('iptables -A INPUT -p udp -s 1.2.1.1')
      shell('iptables -A OUTPUT -s 1.2.1.2 -m comment --comment "010 output-1.2.1.2"')
    end

    it 'purges only the specified chain' do
      pp = <<-EOS
        class { 'firewall': }
        firewallchain { 'INPUT:filter:IPv4':
          purge => true,
        }
      EOS

      apply_manifest(pp, :expect_changes => true)

      shell('iptables-save') do |r|
        expect(r.stdout).to match(/010 output-1\.2\.1\.2/)
        expect(r.stdout).to_not match(/1\.2\.1\.1/)
        expect(r.stderr).to eq("")
      end
    end

    it 'ignores managed rules' do
      pp = <<-EOS
        class { 'firewall': }
        firewallchain { 'OUTPUT:filter:IPv4':
          purge => true,
        }
        firewall { '010 output-1.2.1.2':
          chain  => 'OUTPUT',
          proto  => 'all',
          source => '1.2.1.2',
        }
      EOS

      apply_manifest(pp, :catch_changes => true)
    end

    it 'ignores specified rules' do
      pp = <<-EOS
        class { 'firewall': }
        firewallchain { 'INPUT:filter:IPv4':
          purge => true,
          ignore => [
            '-s 1\.2\.1\.1',
          ],
        }
      EOS

      apply_manifest(pp, :catch_changes => true)
    end

    it 'adds managed rules with ignored rules' do
      pp = <<-EOS
        class { 'firewall': }
        firewallchain { 'INPUT:filter:IPv4':
          purge => true,
          ignore => [
            '-s 1\.2\.1\.1',
          ],
        }
        firewall { '014 input-1.2.1.6':
          chain  => 'INPUT',
          proto  => 'all',
          source => '1.2.1.6',
        }
        -> firewall { '013 input-1.2.1.5':
          chain  => 'INPUT',
          proto  => 'all',
          source => '1.2.1.5',
        }
        -> firewall { '012 input-1.2.1.4':
          chain  => 'INPUT',
          proto  => 'all',
          source => '1.2.1.4',
        }
        -> firewall { '011 input-1.2.1.3':
          chain  => 'INPUT',
          proto  => 'all',
          source => '1.2.1.3',
        }
      EOS

      apply_manifest(pp, :catch_failures => true)

      expect(shell('iptables-save').stdout).to match(/-A INPUT -s 1\.2\.1\.1(\/32)? -p tcp\s?\n-A INPUT -s 1\.2\.1\.1(\/32)? -p udp/)
    end
  end
end
