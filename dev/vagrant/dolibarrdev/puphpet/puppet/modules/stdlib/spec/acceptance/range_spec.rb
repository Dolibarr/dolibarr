#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'range function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'ranges letters' do
      pp = <<-EOS
      $o = range('a','d')
      notice(inline_template('range is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/range is \["a", "b", "c", "d"\]/)
      end
    end
    it 'ranges letters with a step' do
      pp = <<-EOS
      $o = range('a','d', '2')
      notice(inline_template('range is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/range is \["a", "c"\]/)
      end
    end
    it 'ranges letters with a negative step'
    it 'ranges numbers'
    it 'ranges numbers with a step'
    it 'ranges numbers with a negative step'
    it 'ranges numeric strings'
    it 'ranges zero padded numbers'
  end
  describe 'failure' do
    it 'fails with no arguments'
  end
end
