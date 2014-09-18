#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'reverse function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'reverses strings' do
      pp = <<-EOS
      $a = "the public art galleries"
      # Anagram: Large picture halls, I bet
      $o = reverse($a)
      notice(inline_template('reverse is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/reverse is "seirellag tra cilbup eht"/)
      end
    end
  end
  describe 'failure' do
    it 'handles no arguments'
    it 'handles non strings or arrays'
  end
end
