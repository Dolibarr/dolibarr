#
# get_magicvar.rb
#
# This define return the value of the the provided var name
#
module Puppet::Parser::Functions
  newfunction(:get_magicvar, :type => :rvalue, :doc => <<-EOS
This returns the value of the input variable. For example if you input role
it returns the value of $role'.
    EOS
  ) do |arguments|

    raise(Puppet::ParseError, "get_magicvar(): Wrong number of arguments " +
      "given (#{arguments.size} for 1)") if arguments.size < 1

    my_var = arguments[0]
    result = lookupvar("#{my_var}")
    result = 'all' if ( result == :undefined || result == '' )
    return result
  end
end

# vim: set ts=2 sw=2 et :
