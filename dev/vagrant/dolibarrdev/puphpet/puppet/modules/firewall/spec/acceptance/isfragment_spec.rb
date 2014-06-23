require 'spec_helper_acceptance'

describe 'firewall isfragment property' do
  before :all do
    iptables_flush_all_tables
  end

  shared_examples "is idempotent" do |value, line_match|
    it "changes the value to #{value}" do
      pp = <<-EOS
          class { '::firewall': }
          firewall { '597 - test':
            ensure => present,
            proto  => 'tcp',
            #{value}
          }
      EOS

      apply_manifest(pp, :catch_failures => true)
      apply_manifest(pp, :catch_changes => true)

      shell('iptables-save') do |r|
        expect(r.stdout).to match(/#{line_match}/)
      end
    end
  end
  shared_examples "doesn't change" do |value, line_match|
    it "doesn't change the value to #{value}" do
      pp = <<-EOS
          class { '::firewall': }
          firewall { '597 - test':
            ensure => present,
            proto  => 'tcp',
            #{value}
          }
      EOS

      apply_manifest(pp, :catch_changes => true)

      shell('iptables-save') do |r|
        expect(r.stdout).to match(/#{line_match}/)
      end
    end
  end

  describe 'adding a rule' do
    context 'when unset' do
      before :all do
        iptables_flush_all_tables
      end
      it_behaves_like 'is idempotent', '', /-A INPUT -p tcp -m comment --comment "597 - test"/
    end
    context 'when set to true' do
      before :all do
        iptables_flush_all_tables
      end
      it_behaves_like 'is idempotent', 'isfragment => true,', /-A INPUT -p tcp -f -m comment --comment "597 - test"/
    end
    context 'when set to false' do
      before :all do
        iptables_flush_all_tables
      end
      it_behaves_like "is idempotent", 'isfragment => false,', /-A INPUT -p tcp -m comment --comment "597 - test"/
    end
  end
  describe 'editing a rule' do
    context 'when unset or false' do
      before :each do
        iptables_flush_all_tables
        shell('iptables -A INPUT -p tcp -m comment --comment "597 - test"')
      end
      context 'and current value is false' do
        it_behaves_like "doesn't change", 'isfragment => false,', /-A INPUT -p tcp -m comment --comment "597 - test"/
      end
      context 'and current value is true' do
        it_behaves_like "is idempotent", 'isfragment => true,', /-A INPUT -p tcp -f -m comment --comment "597 - test"/
      end
    end
    context 'when set to true' do
      before :each do
        iptables_flush_all_tables
        shell('iptables -A INPUT -p tcp -f -m comment --comment "597 - test"')
      end
      context 'and current value is false' do
        it_behaves_like "is idempotent", 'isfragment => false,', /-A INPUT -p tcp -m comment --comment "597 - test"/
      end
      context 'and current value is true' do
        it_behaves_like "doesn't change", 'isfragment => true,', /-A INPUT -p tcp -f -m comment --comment "597 - test"/
      end
    end
  end
end
