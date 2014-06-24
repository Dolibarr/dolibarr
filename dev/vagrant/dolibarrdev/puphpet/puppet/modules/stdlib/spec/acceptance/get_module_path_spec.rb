#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'get_module_path function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'get_module_paths stdlib' do
      pp = <<-EOS
      $a = $::is_pe ? {
        'true'  => '/opt/puppet/share/puppet/modules/stdlib',
        'false' => '/etc/puppet/modules/stdlib',
      }
      $o = get_module_path('stdlib')
      if $o == $a {
        notify { 'output correct': }
      }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/Notice: output correct/)
      end
    end
    it 'get_module_paths dne' do
      pp = <<-EOS
      $a = $::is_pe ? {
        'true'  => '/etc/puppetlabs/puppet/modules/dne',
        'false' => '/etc/puppet/modules/dne',
      }
      $o = get_module_path('dne')
      if $o == $a {
        notify { 'output correct': }
      }
      EOS

      apply_manifest(pp, :expect_failures => true)
    end
  end
  describe 'failure' do
    it 'handles improper argument counts'
    it 'handles non-numbers'
  end
end
