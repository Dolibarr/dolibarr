require 'spec_helper_acceptance'

case fact('osfamily')
when 'Debian'
  service_name = 'apache2'
when 'FreeBSD'
  service_name = 'apache22'
else
  # Not implemented yet
  service_name = :skip
end

describe 'apache::mod::itk class', :unless => UNSUPPORTED_PLATFORMS.include?(fact('osfamily')) or service_name.equal? :skip do
  describe 'running puppet code' do
    # Using puppet_apply as a helper
    it 'should work with no errors' do
      pp = <<-EOS
          class { 'apache':
            mpm_module => 'itk',
          }
      EOS

      # Run it twice and test for idempotency
      apply_manifest(pp, :catch_failures => true)
      expect(apply_manifest(pp, :catch_failures => true).exit_code).to be_zero
    end
  end

  describe service(service_name) do
    it { should be_running }
    it { should be_enabled }
  end
end
