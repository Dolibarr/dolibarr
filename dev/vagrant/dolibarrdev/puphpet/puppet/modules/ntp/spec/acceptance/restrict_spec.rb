require 'spec_helper_acceptance'

describe "ntp class with restrict:", :unless => UNSUPPORTED_PLATFORMS.include?(fact('osfamily')) do
  context 'should run successfully' do
    pp = "class { 'ntp': restrict => ['test restrict']}"

    it 'runs twice' do
      2.times do
        apply_manifest(pp, :catch_failures => true) do |r|
          expect(r.stderr).to be_empty
        end
      end
    end
  end

  describe file('/etc/ntp.conf') do
    it { should contain('test restrict') }
  end

end
