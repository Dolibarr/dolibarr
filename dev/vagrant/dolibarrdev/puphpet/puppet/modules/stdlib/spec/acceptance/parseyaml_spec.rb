#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'parseyaml function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'parses valid yaml' do
      pp = <<-EOS
      $a = "---\nhunter: washere\ntests: passing\n"
      $o = parseyaml($a)
      $tests = $o['tests']
      notice(inline_template('tests are <%= @tests.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/tests are "passing"/)
      end
    end
  end
  describe 'failure' do
    it 'raises error on incorrect yaml' do
      pp = <<-EOS
      $a = "---\nhunter: washere\ntests: passing\n:"
      $o = parseyaml($a)
      $tests = $o['tests']
      notice(inline_template('tests are <%= @tests.inspect %>'))
      EOS

      apply_manifest(pp, :expect_failures => true) do |r|
        expect(r.stderr).to match(/syntax error/)
      end
    end

    it 'raises error on incorrect number of arguments'
  end
end
