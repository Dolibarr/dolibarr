module Puppet::Parser::Functions

  newfunction(:validate_slength, :doc => <<-'ENDHEREDOC') do |args|
    Validate that the first argument is a string (or an array of strings), and
    less/equal to than the length of the second argument. An optional third
    parameter can be given a the minimum length. It fails if the first
    argument is not a string or array of strings, and if arg 2 and arg 3 are
    not convertable to a number.

    The following values will pass:

      validate_slength("discombobulate",17)
      validate_slength(["discombobulate","moo"],17)
      validate_slength(["discombobulate","moo"],17,3)

    The following valueis will not:

      validate_slength("discombobulate",1)
      validate_slength(["discombobulate","thermometer"],5)
      validate_slength(["discombobulate","moo"],17,10)

    ENDHEREDOC

    raise Puppet::ParseError, "validate_slength(): Wrong number of arguments (#{args.length}; must be 2 or 3)" unless args.length == 2 or args.length == 3

    input, max_length, min_length = *args

    begin
      max_length = Integer(max_length)
      raise ArgumentError if max_length <= 0
    rescue ArgumentError, TypeError
      raise Puppet::ParseError, "validate_slength(): Expected second argument to be a positive Numeric, got #{max_length}:#{max_length.class}"
    end

    if min_length
      begin
        min_length = Integer(min_length)
        raise ArgumentError if min_length < 0
    rescue ArgumentError, TypeError
        raise Puppet::ParseError, "validate_slength(): Expected third argument to be unset or a positive Numeric, got #{min_length}:#{min_length.class}"
      end
    else
      min_length = 0
    end

    if min_length > max_length
      raise Puppet::ParseError, "validate_slength(): Expected second argument to be larger than third argument"
    end

    validator = lambda do |str|
      unless str.length <= max_length and str.length >= min_length
        raise Puppet::ParseError, "validate_slength(): Expected length of #{input.inspect} to be between #{min_length} and #{max_length}, was #{input.length}"
      end
    end

    case input
    when String
      validator.call(input)
    when Array
      input.each_with_index do |arg, pos|
        if arg.is_a? String
          validator.call(arg)
        else
          raise Puppet::ParseError, "validate_slength(): Expected element at array position #{pos} to be a String, got #{arg.class}"
        end
      end
    else
      raise Puppet::ParseError, "validate_slength(): Expected first argument to be a String or Array, got #{input.class}"
    end
  end
end
