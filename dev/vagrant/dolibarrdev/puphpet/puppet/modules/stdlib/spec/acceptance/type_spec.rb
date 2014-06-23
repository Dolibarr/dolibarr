#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'type function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'types arrays' do
      pp = <<-EOS
      $a = ["the","public","art","galleries"]
      # Anagram: Large picture halls, I bet
      $o = type($a)
      notice(inline_template('type is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/type is "array"/)
      end
    end
    it 'types strings' do
      pp = <<-EOS
      $a = "blowzy night-frumps vex'd jack q"
      $o = type($a)
      notice(inline_template('type is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/type is "string"/)
      end
    end
    it 'types hashes'
    it 'types integers'
    it 'types floats'
    it 'types booleans'
  end
  describe 'failure' do
    it 'handles no arguments'
  end
end
