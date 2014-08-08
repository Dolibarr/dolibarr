#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'downcase function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'returns the downcase' do
      pp = <<-EOS
      $a = 'AOEU'
      $b = 'aoeu'
      $o = downcase($a)
      if $o == $b {
        notify { 'output correct': }
      }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/Notice: output correct/)
      end
    end
    it 'doesn\'t affect lowercase words' do
      pp = <<-EOS
      $a = 'aoeu aoeu'
      $b = 'aoeu aoeu'
      $o = downcase($a)
      if $o == $b {
        notify { 'output correct': }
      }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/Notice: output correct/)
      end
    end
  end
  describe 'failure' do
    it 'handles improper argument counts'
    it 'handles non-strings'
  end
end
