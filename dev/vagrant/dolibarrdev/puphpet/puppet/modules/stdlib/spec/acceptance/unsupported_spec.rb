#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'unsupported distributions and OSes', :if => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  it 'should fail' do
    pp = <<-EOS
    class { 'mysql::server': }
    EOS
    expect(apply_manifest(pp, :expect_failures => true).stderr).to match(/unsupported osfamily/i)
  end
end
