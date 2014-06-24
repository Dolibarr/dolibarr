require 'spec_helper_acceptance'

describe 'apt::release class', :unless => UNSUPPORTED_PLATFORMS.include?(fact('osfamily')) do
  context 'release_id' do
    it 'should work with no errors' do
      pp = <<-EOS
      include apt
      class { 'apt::release': release_id => 'precise', }
      EOS

      apply_manifest(pp, :catch_failures => true)
    end

    describe file('/etc/apt/apt.conf.d/01release') do
      it { should be_file }
      it { should contain 'APT::Default-Release "precise";' }
    end
  end

  context 'reset' do
    it 'cleans up' do
      shell('rm -rf /etc/apt/apt.conf.d/01release')
    end
  end

end
