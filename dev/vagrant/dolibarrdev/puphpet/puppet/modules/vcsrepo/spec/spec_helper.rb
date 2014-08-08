require 'puppetlabs_spec_helper/module_spec_helper'
require 'simplecov'
require 'support/filesystem_helpers'
require 'support/fixture_helpers'

SimpleCov.start do
    add_filter "/spec/"
end

RSpec.configure do |c|
  c.include FilesystemHelpers
  c.include FixtureHelpers
end
