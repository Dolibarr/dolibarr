require 'uri'

module Puppet::Parser::Functions
  newfunction(:staging_parse, :type => :rvalue, :doc => <<-EOS
Parse filepath to retrieve information about the file.
    EOS
  ) do |arguments|

    raise(Puppet::ParseError, "staging_parse(): Wrong number of arguments " +
      "given (#{arguments.size} for 1, 2, 3)") if arguments.size < 1 || arguments.size > 3

    source    = arguments[0]
    path      = URI.parse(source).path

    raise Puppet::ParseError, "staging_parse(): #{source.inspect} has no URI " +
      "'path' component" if path.nil?

    info      = arguments[1] ? arguments[1] : 'filename'
    extension = arguments[2] ? arguments[2] : File.extname(path)

    case info
    when 'filename'
      result = File.basename(path)
    when 'basename'
      result = File.basename(path, extension)
    when 'extname'
      result = File.extname(path)
    when 'parent'
      result = File.expand_path(File.join(path, '..'))
    else
      raise Puppet::ParseError, "staging_parse(), unknown parse info #{info}."
    end

    return result
  end
end
