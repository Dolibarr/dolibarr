#! /usr/bin/env ruby -S rspec
require 'rspec'

class Object
  # This is necessary because the RAL has a 'should'
  # method.
  alias :must :should
  alias :must_not :should_not
end
