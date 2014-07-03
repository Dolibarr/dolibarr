#
# intersection.rb
#

module Puppet::Parser::Functions
  newfunction(:intersection, :type => :rvalue, :doc => <<-EOS
This function returns an array an intersection of two.

*Examples:*

    intersection(["a","b","c"],["b","c","d"])

Would return: ["b","c"]
    EOS
  ) do |arguments|

    # Two arguments are required
    raise(Puppet::ParseError, "intersection(): Wrong number of arguments " +
      "given (#{arguments.size} for 2)") if arguments.size != 2

    first = arguments[0]
    second = arguments[1]

    unless first.is_a?(Array) && second.is_a?(Array)
      raise(Puppet::ParseError, 'intersection(): Requires 2 arrays')
    end

    result = first & second

    return result
  end
end

# vim: set ts=2 sw=2 et :
