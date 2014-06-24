require 'spec_helper_system'

describe 'mysql::server::account_security class' do

  describe 'running puppet code' do
    # Using puppet_apply as a helper
    it 'should work with no errors' do
      pp = <<-EOS
        class { 'mysql::server': remove_default_accounts => true }
      EOS

      # Run it twice and test for idempotency
      puppet_apply(pp) do |r|
        r.exit_code.should_not == 1
        r.refresh
        r.exit_code.should be_zero
      end
    end

    describe 'accounts' do
      it 'should delete accounts' do
        shell("mysql -e 'show grants for root@127.0.01;'") do |s|
          s.exit_code.should == 1
        end
      end

     it 'should delete databases' do
      shell("mysql -e 'show databases;' |grep test") do |s|
        s.exit_code.should == 1
      end
     end
    end
  end

end
