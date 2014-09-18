#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'
require 'puppet'

describe 'zip function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'zips two arrays of numbers together' do
      pp = <<-EOS
      $one = [1,2,3,4]
      $two = [5,6,7,8]
      $output = zip($one,$two)
      notice(inline_template('<%= @output.inspect %>'))
      EOS

      expect(apply_manifest(pp, :catch_failures => true).stdout).to match(/\[\["1", "5"\], \["2", "6"\], \["3", "7"\], \["4", "8"\]\]/)
    end
    it 'zips two arrays of numbers & bools together' do
      pp = <<-EOS
      $one = [1,2,"three",4]
      $two = [true,true,false,false]
      $output = zip($one,$two)
      notice(inline_template('<%= @output.inspect %>'))
      EOS

      expect(apply_manifest(pp, :catch_failures => true).stdout).to match(/\[\["1", true\], \["2", true\], \["three", false\], \["4", false\]\]/)
    end
    it 'zips two arrays of numbers together and flattens them' do
      # XXX This only tests the argument `true`, even though the following are valid:
      # 1 t y true yes
      # 0 f n false no
      # undef undefined
      pp = <<-EOS
      $one = [1,2,3,4]
      $two = [5,6,7,8]
      $output = zip($one,$two,true)
      notice(inline_template('<%= @output.inspect %>'))
      EOS

      expect(apply_manifest(pp, :catch_failures => true).stdout).to match(/\["1", "5", "2", "6", "3", "7", "4", "8"\]/)
    end
    it 'handles unmatched length' do
      # XXX Is this expected behavior?
      pp = <<-EOS
      $one = [1,2]
      $two = [5,6,7,8]
      $output = zip($one,$two)
      notice(inline_template('<%= @output.inspect %>'))
      EOS

      expect(apply_manifest(pp, :catch_failures => true).stdout).to match(/\[\["1", "5"\], \["2", "6"\]\]/)
    end
  end
  describe 'failure' do
    it 'handles improper number of arguments' do
      pp = <<-EOS
      $one = [1,2]
      $output = zip($one)
      notice(inline_template('<%= @output.inspect %>'))
      EOS

      expect(apply_manifest(pp, :expect_failures => true).stderr).to match(/Wrong number of arguments/)
    end
    it 'handles improper argument types' do
      pp = <<-EOS
      $one = "a string"
      $two = [5,6,7,8]
      $output = zip($one,$two)
      notice(inline_template('<%= @output.inspect %>'))
      EOS

      expect(apply_manifest(pp, :expect_failures => true).stderr).to match(/Requires array/)
    end
  end
end
