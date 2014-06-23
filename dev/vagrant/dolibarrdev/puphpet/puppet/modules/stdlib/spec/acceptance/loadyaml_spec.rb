#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'loadyaml function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'loadyamls array of values' do
      shell('echo "---
      aaa: 1
      bbb: 2
      ccc: 3
      ddd: 4" > /testyaml.yaml')
      pp = <<-EOS
      $o = loadyaml('/testyaml.yaml')
      notice(inline_template('loadyaml[aaa] is <%= @o["aaa"].inspect %>'))
      notice(inline_template('loadyaml[bbb] is <%= @o["bbb"].inspect %>'))
      notice(inline_template('loadyaml[ccc] is <%= @o["ccc"].inspect %>'))
      notice(inline_template('loadyaml[ddd] is <%= @o["ddd"].inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/loadyaml\[aaa\] is 1/)
        expect(r.stdout).to match(/loadyaml\[bbb\] is 2/)
        expect(r.stdout).to match(/loadyaml\[ccc\] is 3/)
        expect(r.stdout).to match(/loadyaml\[ddd\] is 4/)
      end
    end
  end
  describe 'failure' do
    it 'fails with no arguments'
  end
end
