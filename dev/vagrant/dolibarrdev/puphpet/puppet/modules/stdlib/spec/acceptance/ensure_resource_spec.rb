#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'ensure_resource function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'ensure_resource a package' do
      apply_manifest('package { "zsh": ensure => absent, }')
      pp = <<-EOS
      $a = "zsh"
      ensure_resource('package', $a)
      EOS

      apply_manifest(pp, :expect_changes => true) do |r|
        expect(r.stdout).to match(/Package\[zsh\]\/ensure: created/)
      end
    end
    it 'ensures a resource already declared'
    it 'takes defaults arguments'
  end
  describe 'failure' do
    it 'handles no arguments'
    it 'handles non strings'
  end
end
