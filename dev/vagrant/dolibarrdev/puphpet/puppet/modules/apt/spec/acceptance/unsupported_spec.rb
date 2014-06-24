require 'spec_helper_acceptance'

describe 'unsupported distributions and OSes', :if => UNSUPPORTED_PLATFORMS.include?(fact('osfamily')) do
  it 'class apt fails' do
    pp = <<-EOS
      class { 'apt': }
    EOS
    expect(apply_manifest(pp, :expect_failures => true).stderr).to match(/unsupported/i)
  end
end
