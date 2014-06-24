#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'union function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'unions arrays' do
      pp = <<-EOS
      $a = ["the","public"]
      $b = ["art","galleries"]
      # Anagram: Large picture halls, I bet
      $o = union($a,$b)
      notice(inline_template('union is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/union is \["the", "public", "art", "galleries"\]/)
      end
    end
  end
  describe 'failure' do
    it 'handles no arguments'
    it 'handles non arrays'
  end
end
