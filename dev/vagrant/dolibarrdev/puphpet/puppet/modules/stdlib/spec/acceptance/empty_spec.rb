#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'empty function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'recognizes empty strings' do
      pp = <<-EOS
      $a = ''
      $b = true
      $o = empty($a)
      if $o == $b {
        notify { 'output correct': }
      }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/Notice: output correct/)
      end
    end
    it 'recognizes non-empty strings' do
      pp = <<-EOS
      $a = 'aoeu'
      $b = false
      $o = empty($a)
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
