#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'values function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'returns an array of values' do
      pp = <<-EOS
      $arg = {
        'a' => 1,
        'b' => 2,
        'c' => 3,
      }
      $output = values($arg)
      notice(inline_template('<%= @output.sort.inspect %>'))
      EOS

      expect(apply_manifest(pp, :catch_failures => true).stdout).to match(/\["1", "2", "3"\]/)
    end
  end
  describe 'failure' do
    it 'handles non-hash arguments' do
      pp = <<-EOS
      $arg = "foo"
      $output = values($arg)
      notice(inline_template('<%= @output.inspect %>'))
      EOS

      expect(apply_manifest(pp, :expect_failures => true).stderr).to match(/Requires hash/)
    end
  end
end
