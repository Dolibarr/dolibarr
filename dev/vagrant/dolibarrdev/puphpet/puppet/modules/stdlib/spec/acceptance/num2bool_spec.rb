#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'num2bool function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'bools positive numbers and numeric strings as true' do
      pp = <<-EOS
      $a = 1
      $b = "1"
      $c = "50"
      $ao = num2bool($a)
      $bo = num2bool($b)
      $co = num2bool($c)
      notice(inline_template('a is <%= @ao.inspect %>'))
      notice(inline_template('b is <%= @bo.inspect %>'))
      notice(inline_template('c is <%= @co.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/a is true/)
        expect(r.stdout).to match(/b is true/)
        expect(r.stdout).to match(/c is true/)
      end
    end
    it 'bools negative numbers as false' do
      pp = <<-EOS
      $a = 0
      $b = -0.1
      $c = ["-50","1"]
      $ao = num2bool($a)
      $bo = num2bool($b)
      $co = num2bool($c)
      notice(inline_template('a is <%= @ao.inspect %>'))
      notice(inline_template('b is <%= @bo.inspect %>'))
      notice(inline_template('c is <%= @co.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/a is false/)
        expect(r.stdout).to match(/b is false/)
        expect(r.stdout).to match(/c is false/)
      end
    end
  end
  describe 'failure' do
    it 'fails on words' do
      pp = <<-EOS
      $a = "a"
      $ao = num2bool($a)
      notice(inline_template('a is <%= @ao.inspect %>'))
      EOS
      expect(apply_manifest(pp, :expect_failures => true).stderr).to match(/not look like a number/)
    end

    it 'fails on numberwords' do
      pp = <<-EOS
      $b = "1b"
      $bo = num2bool($b)
      notice(inline_template('b is <%= @bo.inspect %>'))
      EOS
      expect(apply_manifest(pp, :expect_failures => true).stderr).to match(/not look like a number/)

    end

    it 'fails on non-numeric/strings' do
      pending "The function will call .to_s.to_i on anything not a Numeric or
      String, and results in 0. Is this intended?"
      pp = <<-EOS
      $c = {"c" => "-50"}
      $co = num2bool($c)
      notice(inline_template('c is <%= @co.inspect %>'))
      EOS
      expect(apply_manifest(ppc :expect_failures => true).stderr).to match(/Unable to parse/)
    end
  end
end
