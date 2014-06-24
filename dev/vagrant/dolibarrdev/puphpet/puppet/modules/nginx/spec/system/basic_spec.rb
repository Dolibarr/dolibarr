require 'spec_helper_system'

# Here we put the more basic fundamental tests, ultra obvious stuff.
describe "basic tests:" do
  context 'make sure we have copied the module across' do
    # No point diagnosing any more if the module wasn't copied properly
    context shell 'ls /etc/puppet/modules/nginx' do
      its(:stdout) { should =~ /Modulefile/ }
      its(:stderr) { should be_empty }
      its(:exit_code) { should be_zero }
    end
  end

  #puppet smoke test
  context puppet_apply 'notice("foo")' do
    its(:stdout) { should =~ /foo/ }
    its(:stderr) { should be_empty }
    its(:exit_code) { should be_zero }
  end

  it 'nginx class should work with no errors' do
    pp = <<-EOS
      class { 'nginx': }
    EOS

    # Run it twice and test for idempotency
    puppet_apply(pp) do |r|
      [0,2].should include(r.exit_code)
      r.refresh
      r.exit_code.should be_zero
    end
  end
end
