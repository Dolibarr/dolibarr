#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'suffix function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'suffixes array of values' do
      pp = <<-EOS
      $o = suffix(['a','b','c'],'p')
      notice(inline_template('suffix is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/suffix is \["ap", "bp", "cp"\]/)
      end
    end
    it 'suffixs with empty array' do
      pp = <<-EOS
      $o = suffix([],'p')
      notice(inline_template('suffix is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/suffix is \[\]/)
      end
    end
    it 'suffixs array of values with undef' do
      pp = <<-EOS
      $o = suffix(['a','b','c'], undef)
      notice(inline_template('suffix is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/suffix is \["a", "b", "c"\]/)
      end
    end
  end
  describe 'failure' do
    it 'fails with no arguments'
    it 'fails when first argument is not array'
    it 'fails when second argument is not string'
  end
end
