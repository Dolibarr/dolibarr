#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'keys function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'keyss hashes' do
      pp = <<-EOS
      $a = {'aaa'=>'bbb','ccc'=>'ddd'}
      $o = keys($a)
      notice(inline_template('keys is <%= @o.sort.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/keys is \["aaa", "ccc"\]/)
      end
    end
    it 'handles non hashes'
    it 'handles empty hashes'
  end
  describe 'failure' do
    it 'handles improper argument counts'
  end
end
