#! /usr/bin/env ruby -S rspec
require 'spec_helper'
require 'puppet_spec/compiler'

describe "anchorrefresh" do
  include PuppetSpec::Compiler

  let :transaction do
    apply_compiled_manifest(<<-ANCHORCLASS)
      class anchored {
        anchor { 'anchored::begin': }
        ~> anchor { 'anchored::end': }
      }

      class anchorrefresh {
        notify { 'first': }
        ~> class { 'anchored': }
        ~> anchor { 'final': }
      }

      include anchorrefresh
    ANCHORCLASS
  end

  it 'propagates events through the anchored class' do
    resource = transaction.resource_status('Anchor[final]')

    expect(resource.restarted).to eq(true)
  end
end
