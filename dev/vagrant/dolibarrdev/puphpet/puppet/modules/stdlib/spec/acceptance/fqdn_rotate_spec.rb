#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'fqdn_rotate function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    let(:facts_d) do
      if fact('is_pe') == "true"
        '/etc/puppetlabs/facter/facts.d'
      else
        '/etc/facter/facts.d'
      end
    end
    after :each do
      shell("if [ -f #{facts_d}/fqdn.txt ] ; then rm #{facts_d}/fqdn.txt ; fi")
    end
    it 'fqdn_rotates floats' do
      shell("echo 'fqdn=fakehost.localdomain' > #{facts_d}/fqdn.txt")
      pp = <<-EOS
      $a = ['a','b','c','d']
      $o = fqdn_rotate($a)
      notice(inline_template('fqdn_rotate is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/fqdn_rotate is \["c", "d", "a", "b"\]/)
      end
    end
  end
  describe 'failure' do
    it 'handles improper argument counts'
    it 'handles non-numbers'
  end
end
