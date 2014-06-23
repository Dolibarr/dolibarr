#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'is_hash function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'is_hashs arrays' do
      pp = <<-EOS
      $a = ['aaa','bbb','ccc']
      $o = is_hash($a)
      notice(inline_template('is_hash is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/is_hash is false/)
      end
    end
    it 'is_hashs empty hashs' do
      pp = <<-EOS
      $a = {}
      $b = true
      $o = is_hash($a)
      if $o == $b {
        notify { 'output correct': }
      }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/Notice: output correct/)
      end
    end
    it 'is_hashs strings' do
      pp = <<-EOS
      $a = "aoeu"
      $b = false
      $o = is_hash($a)
      if $o == $b {
        notify { 'output correct': }
      }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/Notice: output correct/)
      end
    end
    it 'is_hashs hashes' do
      pp = <<-EOS
      $a = {'aaa'=>'bbb'}
      $b = true
      $o = is_hash($a)
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
  end
end
