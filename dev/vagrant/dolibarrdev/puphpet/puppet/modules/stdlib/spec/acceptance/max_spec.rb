#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'max function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'maxs arrays' do
      pp = <<-EOS
      $o = max("the","public","art","galleries")
      notice(inline_template('max is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/max is "the"/)
      end
    end
  end
  describe 'failure' do
    it 'handles no arguments'
  end
end
