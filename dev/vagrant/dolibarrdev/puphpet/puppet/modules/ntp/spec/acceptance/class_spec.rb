require 'spec_helper_acceptance'

describe 'ntp class:', :unless => UNSUPPORTED_PLATFORMS.include?(fact('osfamily')) do
  it 'should run successfully' do
    pp = "class { 'ntp': }"

    # Apply twice to ensure no errors the second time.
    apply_manifest(pp, :catch_failures => true) do |r|
      expect(r.stderr).to eq("")
    end
    apply_manifest(pp, :catch_failures => true) do |r|
      expect(r.stderr).to eq("")
      expect(r.exit_code).to be_zero
    end
  end

  context 'service_ensure => stopped:' do
    it 'runs successfully' do
      pp = "class { 'ntp': service_ensure => stopped }"

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stderr).to eq("")
      end
    end
  end

  context 'service_ensure => running:' do
    it 'runs successfully' do
      pp = "class { 'ntp': service_ensure => running }"

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stderr).to eq("")
      end
    end
  end
end
