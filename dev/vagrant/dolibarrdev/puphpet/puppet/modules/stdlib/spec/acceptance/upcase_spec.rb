#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'upcase function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'upcases arrays' do
      pp = <<-EOS
      $a = ["wallless", "laparohysterosalpingooophorectomy", "brrr", "goddessship"]
      $o = upcase($a)
      notice(inline_template('upcase is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/upcase is \["WALLLESS", "LAPAROHYSTEROSALPINGOOOPHORECTOMY", "BRRR", "GODDESSSHIP"\]/)
      end
    end
    it 'upcases strings' do
      pp = <<-EOS
      $a = "wallless laparohysterosalpingooophorectomy brrr goddessship"
      $o = upcase($a)
      notice(inline_template('upcase is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/upcase is "WALLLESS LAPAROHYSTEROSALPINGOOOPHORECTOMY BRRR GODDESSSHIP"/)
      end
    end
  end
  describe 'failure' do
    it 'handles no arguments'
    it 'handles non strings or arrays'
  end
end
