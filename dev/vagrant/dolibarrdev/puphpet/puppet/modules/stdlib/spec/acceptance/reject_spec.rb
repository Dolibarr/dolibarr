#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'reject function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'rejects array of values' do
      pp = <<-EOS
      $o = reject(['aaa','bbb','ccc','aaaddd'], 'aaa')
      notice(inline_template('reject is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/reject is \["bbb", "ccc"\]/)
      end
    end
    it 'rejects with empty array' do
      pp = <<-EOS
      $o = reject([],'aaa')
      notice(inline_template('reject is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/reject is \[\]/)
      end
    end
    it 'rejects array of values with undef' do
      pp = <<-EOS
      $o = reject(['aaa','bbb','ccc','aaaddd'], undef)
      notice(inline_template('reject is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/reject is \[\]/)
      end
    end
  end
  describe 'failure' do
    it 'fails with no arguments'
    it 'fails when first argument is not array'
    it 'fails when second argument is not string'
  end
end
