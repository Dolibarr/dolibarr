require 'spec_helper_acceptance'

describe 'unsupported distributions and OSes', :if => UNSUPPORTED_PLATFORMS.include?(fact('osfamily')) do
  it 'should fail for client' do
    pp = <<-EOS
    class { 'postgresql::client': }
    EOS
    expect(apply_manifest(pp, :expect_failures => true).stderr).to match(/does not provide defaults for osfamily/i)
  end
  it 'should fail for server' do
    pp = <<-EOS
    class { 'postgresql::server': }
    EOS
    expect(apply_manifest(pp, :expect_failures => true).stderr).to match(/does not provide defaults for osfamily/i)
  end
end
