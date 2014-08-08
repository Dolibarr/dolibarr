require 'spec_helper_acceptance'

if default['platform'] =~ /el-5/
  describe "firewall ip6tables doesn't work on 1.3.5 because --comment is missing" do
    before :all do
      ip6tables_flush_all_tables
    end

    it "can't use ip6tables" do
      pp = <<-EOS
        class { '::firewall': }
        firewall { '599 - test':
          ensure   => present,
          proto    => 'tcp',
          provider => 'ip6tables',
        }
      EOS
      expect(apply_manifest(pp, :expect_failures => true).stderr).to match(/ip6tables provider is not supported/)
    end
  end
else
  describe 'firewall ishasmorefrags/islastfrag/isfirstfrag properties' do
    before :all do
      ip6tables_flush_all_tables
    end

    shared_examples "is idempotent" do |values, line_match|
      it "changes the values to #{values}" do
        pp = <<-EOS
            class { '::firewall': }
            firewall { '599 - test':
              ensure   => present,
              proto    => 'tcp',
              provider => 'ip6tables',
              #{values}
            }
        EOS

        apply_manifest(pp, :catch_failures => true)
        apply_manifest(pp, :catch_changes => true)

        shell('ip6tables-save') do |r|
          expect(r.stdout).to match(/#{line_match}/)
        end
      end
    end
    shared_examples "doesn't change" do |values, line_match|
      it "doesn't change the values to #{values}" do
        pp = <<-EOS
            class { '::firewall': }
            firewall { '599 - test':
              ensure   => present,
              proto    => 'tcp',
              provider => 'ip6tables',
              #{values}
            }
        EOS

        apply_manifest(pp, :catch_changes => true)

        shell('ip6tables-save') do |r|
          expect(r.stdout).to match(/#{line_match}/)
        end
      end
    end

    describe 'adding a rule' do
      context 'when unset' do
        before :all do
          ip6tables_flush_all_tables
        end
        it_behaves_like 'is idempotent', '', /-A INPUT -p tcp -m comment --comment "599 - test"/
      end
      context 'when set to true' do
        before :all do
          ip6tables_flush_all_tables
        end
        it_behaves_like "is idempotent", 'ishasmorefrags => true, islastfrag => true, isfirstfrag => true', /-A INPUT -p tcp -m frag --fragid 0 --fragmore -m frag --fragid 0 --fraglast -m frag --fragid 0 --fragfirst -m comment --comment "599 - test"/
      end
      context 'when set to false' do
        before :all do
          ip6tables_flush_all_tables
        end
        it_behaves_like "is idempotent", 'ishasmorefrags => false, islastfrag => false, isfirstfrag => false', /-A INPUT -p tcp -m comment --comment "599 - test"/
      end
    end
    describe 'editing a rule' do
      context 'when unset or false' do
        before :each do
          ip6tables_flush_all_tables
          shell('ip6tables -A INPUT -p tcp -m comment --comment "599 - test"')
        end
        context 'and current value is false' do
          it_behaves_like "doesn't change", 'ishasmorefrags => false, islastfrag => false, isfirstfrag => false', /-A INPUT -p tcp -m comment --comment "599 - test"/
        end
        context 'and current value is true' do
          it_behaves_like "is idempotent", 'ishasmorefrags => true, islastfrag => true, isfirstfrag => true', /-A INPUT -p tcp -m frag --fragid 0 --fragmore -m frag --fragid 0 --fraglast -m frag --fragid 0 --fragfirst -m comment --comment "599 - test"/
        end
      end
      context 'when set to true' do
        before :each do
          ip6tables_flush_all_tables
          shell('ip6tables -A INPUT -p tcp -m frag --fragid 0 --fragmore -m frag --fragid 0 --fraglast -m frag --fragid 0 --fragfirst -m comment --comment "599 - test"')
        end
        context 'and current value is false' do
          it_behaves_like "is idempotent", 'ishasmorefrags => false, islastfrag => false, isfirstfrag => false', /-A INPUT -p tcp -m comment --comment "599 - test"/
        end
        context 'and current value is true' do
          it_behaves_like "doesn't change", 'ishasmorefrags => true, islastfrag => true, isfirstfrag => true', /-A INPUT -p tcp -m frag --fragid 0 --fragmore -m frag --fragid 0 --fraglast -m frag --fragid 0 --fragfirst -m comment --comment "599 - test"/
        end
      end
    end
  end
end
