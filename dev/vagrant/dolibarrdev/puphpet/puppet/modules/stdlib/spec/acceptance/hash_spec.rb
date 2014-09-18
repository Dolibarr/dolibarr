#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'hash function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'hashs arrays' do
      pp = <<-EOS
      $a = ['aaa','bbb','bbb','ccc','ddd','eee']
      $b = { 'aaa' => 'bbb', 'bbb' => 'ccc', 'ddd' => 'eee' }
      $o = hash($a)
      if $o == $b {
        notify { 'output correct': }
      }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/Notice: output correct/)
      end
    end
    it 'handles odd-length arrays'
  end
  describe 'failure' do
    it 'handles improper argument counts'
    it 'handles non-arrays'
  end
end
