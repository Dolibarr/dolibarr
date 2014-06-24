#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'to_bytes function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'converts kB to B' do
      pp = <<-EOS
      $o = to_bytes('4 kB')
      notice(inline_template('to_bytes is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        m = r.stdout.match(/to_bytes is (\d+)\D/)
        expect(m[1]).to eq("4096")
      end
    end
    it 'works without the B in unit'
    it 'works without a space before unit'
    it 'works without a unit'
    it 'converts fractions'
  end
  describe 'failure' do
    it 'handles no arguments'
    it 'handles non integer arguments'
    it 'handles unknown units like uB'
  end
end
