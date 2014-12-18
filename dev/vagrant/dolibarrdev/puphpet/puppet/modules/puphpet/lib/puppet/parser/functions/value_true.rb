#
# value_true.rb
#

module Puppet::Parser::Functions

  newfunction(:value_true, :type => :rvalue, :doc => <<-'ENDHEREDOC') do |args|

    Returns true if value is truthy
    ENDHEREDOC

    unless args.length == 1
      raise Puppet::ParseError, ("value_true(): wrong number of arguments (#{args.length}; must be 1)")
    end

    value = args[0]

    if value.nil?
      return false
    end

    if value == false
      return false
    end

    if value == 0
      return false
    end

    if value == '0'
      return false
    end

    if value == 'false'
      return false
    end

    if value.empty?
      return false
    end

    return true

  end
end
