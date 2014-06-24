#
# hash_key_equals.rb
#

module Puppet::Parser::Functions

  newfunction(:hash_key_equals, :type => :rvalue, :doc => <<-'ENDHEREDOC') do |args|

    Returns true if the variable passed to this function is a hash,
    it contains the key requested, and the key matches value.
    ENDHEREDOC

    unless args.length == 3
      raise Puppet::ParseError, ("hash_key_equals(): wrong number of arguments (#{args.length}; must be 3)")
    end

    hashVar       = args[0]
    keyVar        = args[1]
    expectedValue = args[2]

    unless hashVar.is_a?(Hash)
      return false
    end

    unless hashVar.has_key?(keyVar)
      return false
    end

    unless hashVar[keyVar].to_i == expectedValue.to_i
      return false
    end

    return true

  end
end
