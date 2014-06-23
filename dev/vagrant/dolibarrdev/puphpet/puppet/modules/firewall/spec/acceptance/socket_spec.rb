require 'spec_helper_acceptance'

# RHEL5 does not support -m socket
describe 'firewall socket property', :unless => (default['platform'] =~ /el-5/ || fact('operatingsystem') == 'SLES') do
  before :all do
    iptables_flush_all_tables
  end

  shared_examples "is idempotent" do |value, line_match|
    it "changes the value to #{value}" do
      pp = <<-EOS
          class { '::firewall': }
          firewall { '598 - test':
            ensure => present,
            proto  => 'tcp',
            chain  => 'PREROUTING',
            table  => 'raw',
            #{value}
          }
      EOS

      apply_manifest(pp, :catch_failures => true)
      apply_manifest(pp, :catch_changes => true)

      shell('iptables-save -t raw') do |r|
        expect(r.stdout).to match(/#{line_match}/)
      end
    end
  end
  shared_examples "doesn't change" do |value, line_match|
    it "doesn't change the value to #{value}" do
      pp = <<-EOS
          class { '::firewall': }
          firewall { '598 - test':
            ensure => present,
            proto  => 'tcp',
            chain  => 'PREROUTING',
            table  => 'raw',
            #{value}
          }
      EOS

      apply_manifest(pp, :catch_changes => true)

      shell('iptables-save -t raw') do |r|
        expect(r.stdout).to match(/#{line_match}/)
      end
    end
  end

  describe 'adding a rule' do
    context 'when unset' do
      before :all do
        iptables_flush_all_tables
      end
      it_behaves_like 'is idempotent', '', /-A PREROUTING -p tcp -m comment --comment "598 - test"/
    end
    context 'when set to true' do
      before :all do
        iptables_flush_all_tables
      end
      it_behaves_like 'is idempotent', 'socket => true,', /-A PREROUTING -p tcp -m socket -m comment --comment "598 - test"/
    end
    context 'when set to false' do
      before :all do
        iptables_flush_all_tables
      end
      it_behaves_like "is idempotent", 'socket => false,', /-A PREROUTING -p tcp -m comment --comment "598 - test"/
    end
  end
  describe 'editing a rule' do
    context 'when unset or false' do
      before :each do
        iptables_flush_all_tables
        shell('iptables -t raw -A PREROUTING -p tcp -m comment --comment "598 - test"')
      end
      context 'and current value is false' do
        it_behaves_like "doesn't change", 'socket => false,', /-A PREROUTING -p tcp -m comment --comment "598 - test"/
      end
      context 'and current value is true' do
        it_behaves_like "is idempotent", 'socket => true,', /-A PREROUTING -p tcp -m socket -m comment --comment "598 - test"/
      end
    end
    context 'when set to true' do
      before :each do
        iptables_flush_all_tables
        shell('iptables -t raw -A PREROUTING -p tcp -m socket -m comment --comment "598 - test"')
      end
      context 'and current value is false' do
        it_behaves_like "is idempotent", 'socket => false,', /-A PREROUTING -p tcp -m comment --comment "598 - test"/
      end
      context 'and current value is true' do
        it_behaves_like "doesn't change", 'socket => true,', /-A PREROUTING -p tcp -m socket -m comment --comment "598 - test"/
      end
    end
  end
end
