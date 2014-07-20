#
# Converts the array to a csv string
#
# $array = [ 'string1', 'string2', 'string3' ]
#
# becomes:
#
# $string = "string1,string2,string3"
#
module Puppet::Parser::Functions
  newfunction(:array2csv, :type => :rvalue, :doc => <<-'EOS'
    Returns a sorted csv formatted string from an array in the form
    VALUE1,VALUE2,VALUE3
    EOS
  ) do |args|

    raise(Puppet::ParseError, "array2csv(): Wrong number of arguments " +
      "given (#{args.size} of 1)") if args.size < 1

    array = args[0]

    unless array.is_a?(Array)
      raise(Puppet::ParseError, 'array2csv(): Requires an Array')
    end

    sorted_array = array.sort
    result = ''

    sorted_array.each {|value|
      result += "#{value},"
    }

    return result.chop!
    
  end
end