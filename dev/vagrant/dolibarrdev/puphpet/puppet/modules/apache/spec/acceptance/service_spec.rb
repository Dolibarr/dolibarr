require 'spec_helper_acceptance'

describe 'apache::service class', :unless => UNSUPPORTED_PLATFORMS.include?(fact('osfamily')) do
  describe 'adding dependencies in between the base class and service class' do
    it 'should work with no errors' do
      pp = <<-EOS
      class { 'apache': }
      file { '/tmp/test':
        require => Class['apache'],
        notify  => Class['apache::service'],
      }
      EOS

      # Run it twice and test for idempotency
      apply_manifest(pp, :catch_failures => true)
      expect(apply_manifest(pp, :catch_failures => true).exit_code).to be_zero
    end
  end
end
