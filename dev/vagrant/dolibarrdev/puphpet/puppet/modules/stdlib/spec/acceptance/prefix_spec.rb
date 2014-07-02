#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'prefix function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'prefixes array of values' do
      pp = <<-EOS
      $o = prefix(['a','b','c'],'p')
      notice(inline_template('prefix is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/prefix is \["pa", "pb", "pc"\]/)
      end
    end
    it 'prefixs with empty array' do
      pp = <<-EOS
      $o = prefix([],'p')
      notice(inline_template('prefix is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/prefix is \[\]/)
      end
    end
    it 'prefixs array of values with undef' do
      pp = <<-EOS
      $o = prefix(['a','b','c'], undef)
      notice(inline_template('prefix is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/prefix is \["a", "b", "c"\]/)
      end
    end
  end
  describe 'failure' do
    it 'fails with no arguments'
    it 'fails when first argument is not array'
    it 'fails when second argument is not string'
  end
end
