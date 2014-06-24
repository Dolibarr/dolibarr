#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'dirname function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    context 'absolute path' do
      it 'returns the dirname' do
        pp = <<-EOS
        $a = '/path/to/a/file.txt'
        $b = '/path/to/a'
        $o = dirname($a)
        if $o == $b {
          notify { 'output correct': }
        }
        EOS

        apply_manifest(pp, :catch_failures => true) do |r|
          expect(r.stdout).to match(/Notice: output correct/)
        end
      end
    end
    context 'relative path' do
      it 'returns the dirname' do
        pp = <<-EOS
        $a = 'path/to/a/file.txt'
        $b = 'path/to/a'
        $o = dirname($a)
        if $o == $b {
          notify { 'output correct': }
        }
        EOS

        apply_manifest(pp, :catch_failures => true) do |r|
          expect(r.stdout).to match(/Notice: output correct/)
        end
      end
    end
  end
  describe 'failure' do
    it 'handles improper argument counts'
  end
end
