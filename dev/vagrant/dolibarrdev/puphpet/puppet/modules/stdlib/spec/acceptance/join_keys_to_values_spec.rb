#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'join_keys_to_values function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'join_keys_to_valuess hashes' do
      pp = <<-EOS
      $a = {'aaa'=>'bbb','ccc'=>'ddd'}
      $b = ':'
      $o = join_keys_to_values($a,$b)
      notice(inline_template('join_keys_to_values is <%= @o.sort.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/join_keys_to_values is \["aaa:bbb", "ccc:ddd"\]/)
      end
    end
    it 'handles non hashes'
    it 'handles empty hashes'
  end
  describe 'failure' do
    it 'handles improper argument counts'
  end
end
