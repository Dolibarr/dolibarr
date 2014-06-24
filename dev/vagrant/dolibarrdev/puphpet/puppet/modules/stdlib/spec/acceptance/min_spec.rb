#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'min function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'mins arrays' do
      pp = <<-EOS
      $o = min("the","public","art","galleries")
      notice(inline_template('min is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/min is "art"/)
      end
    end
  end
  describe 'failure' do
    it 'handles no arguments'
  end
end
