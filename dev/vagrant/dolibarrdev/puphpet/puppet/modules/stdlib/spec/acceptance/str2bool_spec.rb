#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'str2bool function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'works with "y"' do
      pp = <<-EOS
      $o = str2bool('y')
      notice(inline_template('str2bool is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/str2bool is true/)
      end
    end
    it 'works with "Y"'
    it 'works with "yes"'
    it 'works with "1"'
    it 'works with "true"'
    it 'works with "n"'
    it 'works with "N"'
    it 'works with "no"'
    it 'works with "0"'
    it 'works with "false"'
    it 'works with undef'
  end
  describe 'failure' do
    it 'handles no arguments'
    it 'handles non arrays or strings'
  end
end
