module Puppet::Parser::Functions
  newfunction(:floor, :type => :rvalue, :doc => <<-EOS
    Returns the largest integer less or equal to the argument.
    Takes a single numeric value as an argument.
    EOS
  ) do |arguments|

    raise(Puppet::ParseError, "floor(): Wrong number of arguments " +
          "given (#{arguments.size} for 1)") if arguments.size != 1

    begin
      arg = Float(arguments[0])
    rescue TypeError, ArgumentError => e
      raise(Puppet::ParseError, "floor(): Wrong argument type " +
            "given (#{arguments[0]} for Numeric)")
    end

    raise(Puppet::ParseError, "floor(): Wrong argument type " +
          "given (#{arg.class} for Numeric)") if arg.is_a?(Numeric) == false

    arg.floor
  end
end

# vim: set ts=2 sw=2 et :
