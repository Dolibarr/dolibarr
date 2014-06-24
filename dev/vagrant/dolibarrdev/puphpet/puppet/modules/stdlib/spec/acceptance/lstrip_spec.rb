#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'lstrip function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'lstrips arrays' do
      pp = <<-EOS
      $a = ["  the   ","   public   ","   art","galleries   "]
      # Anagram: Large picture halls, I bet
      $o = lstrip($a)
      notice(inline_template('lstrip is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/lstrip is \["the   ", "public   ", "art", "galleries   "\]/)
      end
    end
    it 'lstrips strings' do
      pp = <<-EOS
      $a = "   blowzy night-frumps vex'd jack q   "
      $o = lstrip($a)
      notice(inline_template('lstrip is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/lstrip is "blowzy night-frumps vex'd jack q   "/)
      end
    end
  end
  describe 'failure' do
    it 'handles no arguments'
    it 'handles non strings or arrays'
  end
end
