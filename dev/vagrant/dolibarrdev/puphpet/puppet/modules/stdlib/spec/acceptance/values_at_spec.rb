#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'values_at function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'returns a specific value' do
      pp = <<-EOS
      $one = ['a','b','c','d','e']
      $two = 1
      $output = values_at($one,$two)
      notice(inline_template('<%= @output.inspect %>'))
      EOS

      expect(apply_manifest(pp, :catch_failures => true).stdout).to match(/\["b"\]/)
    end
    it 'returns a specific negative index value' do
      pending("negative numbers don't work")
      pp = <<-EOS
      $one = ['a','b','c','d','e']
      $two = -1
      $output = values_at($one,$two)
      notice(inline_template('<%= @output.inspect %>'))
      EOS

      expect(apply_manifest(pp, :catch_failures => true).stdout).to match(/\["e"\]/)
    end
    it 'returns a range of values' do
      pp = <<-EOS
      $one = ['a','b','c','d','e']
      $two = "1-3"
      $output = values_at($one,$two)
      notice(inline_template('<%= @output.inspect %>'))
      EOS

      expect(apply_manifest(pp, :catch_failures => true).stdout).to match(/\["b", "c", "d"\]/)
    end
    it 'returns a negative specific value and range of values' do
      pp = <<-EOS
      $one = ['a','b','c','d','e']
      $two = ["1-3",0]
      $output = values_at($one,$two)
      notice(inline_template('<%= @output.inspect %>'))
      EOS

      expect(apply_manifest(pp, :catch_failures => true).stdout).to match(/\["b", "c", "d", "a"\]/)
    end
  end
  describe 'failure' do
    it 'handles improper number of arguments' do
      pp = <<-EOS
      $one = ['a','b','c','d','e']
      $output = values_at($one)
      notice(inline_template('<%= @output.inspect %>'))
      EOS

      expect(apply_manifest(pp, :expect_failures => true).stderr).to match(/Wrong number of arguments/)
    end
    it 'handles non-indicies arguments' do
      pp = <<-EOS
      $one = ['a','b','c','d','e']
      $two = []
      $output = values_at($one,$two)
      notice(inline_template('<%= @output.inspect %>'))
      EOS

      expect(apply_manifest(pp, :expect_failures => true).stderr).to match(/at least one positive index/)
    end

    it 'detects index ranges smaller than the start range'
    it 'handles index ranges larger than array'
    it 'handles non-integer indicies'
  end
end
