#
# is_float.rb
#

module Puppet::Parser::Functions
  newfunction(:is_float, :type => :rvalue, :doc => <<-EOS
Returns true if the variable passed to this function is a float.
    EOS
  ) do |arguments|

    if (arguments.size != 1) then
      raise(Puppet::ParseError, "is_float(): Wrong number of arguments "+
        "given #{arguments.size} for 1")
    end

    value = arguments[0]

    # Only allow Numeric or String types
    return false unless value.is_a?(Numeric) or value.is_a?(String)

    if value != value.to_f.to_s and !value.is_a? Float then
      return false
    else
      return true
    end

  end
end

# vim: set ts=2 sw=2 et :
