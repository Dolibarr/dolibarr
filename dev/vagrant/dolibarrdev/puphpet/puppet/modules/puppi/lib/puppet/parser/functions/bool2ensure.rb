#
# bool2ensure.rb
#
# This define return present/absent accroding to the boolean value passed
#
module Puppet::Parser::Functions
  newfunction(:bool2ensure, :type => :rvalue, :doc => <<-EOS
This converts any input similar to a boolean to the stringpresent or absent
    EOS
  ) do |arguments|

    raise(Puppet::ParseError, "bool2ensure(): Wrong number of arguments " +
      "given (#{arguments.size} for 1)") if arguments.size < 1

    string = arguments[0]

    result = case string
      when false then "absent"
      when true then "present"
      when /^$/, '' then "present" 
      when /^(1|t|y|true|yes)$/  then "present"
      when /^(0|f|n|false|no)$/  then "absent"
      when /^(undef|undefined)$/ then "present"
      else
        raise(Puppet::ParseError, 'bool2ensure(): Unknown type of boolean given')
    end

    return result
  end
end

# vim: set ts=2 sw=2 et :
