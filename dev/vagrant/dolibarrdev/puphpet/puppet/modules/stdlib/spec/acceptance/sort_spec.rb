#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'sort function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'sorts arrays' do
      pp = <<-EOS
      $a = ["the","public","art","galleries"]
      # Anagram: Large picture halls, I bet
      $o = sort($a)
      notice(inline_template('sort is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/sort is \["art", "galleries", "public", "the"\]/)
      end
    end
    it 'sorts strings' do
      pp = <<-EOS
      $a = "blowzy night-frumps vex'd jack q"
      $o = sort($a)
      notice(inline_template('sort is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/sort is "    '-abcdefghijklmnopqrstuvwxyz"/)
      end
    end
  end
  describe 'failure' do
    it 'handles no arguments'
    it 'handles non strings or arrays'
  end
end
