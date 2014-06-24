require 'spec_helper_acceptance'

describe 'preferred servers', :unless => UNSUPPORTED_PLATFORMS.include?(fact('osfamily')) do
  pp = <<-EOS
      class { '::ntp':
        servers           => ['a', 'b', 'c', 'd'],
        preferred_servers => ['c', 'd'],
      }
  EOS

  it 'applies cleanly' do
    apply_manifest(pp, :catch_failures => true) do |r|
      expect(r.stderr).to eq("")
    end
  end

  describe file('/etc/ntp.conf') do
    it { should be_file }
    it { should contain 'server a' }
    it { should contain 'server b' }
    it { should contain 'server c prefer' }
    it { should contain 'server d prefer' }
  end
end
