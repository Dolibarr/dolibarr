require 'digest/md5'

module Puppet::Parser::Functions
  newfunction(:postgresql_escape, :type => :rvalue, :doc => <<-EOS
    Safely escapes a string using $$ using a random tag which should be consistent
    EOS
  ) do |args|

    raise(Puppet::ParseError, "postgresql_escape(): Wrong number of arguments " +
      "given (#{args.size} for 1)") if args.size != 1

    password = args[0]

    if password !~ /\$\$/ 
      retval = "$$#{password}$$"
    else
      escape = Digest::MD5.hexdigest(password)[0..5].gsub(/\d/,'')
      until password !~ /#{escape}/
        escape = Digest::MD5.hexdigest(escape)[0..5].gsub(/\d/,'')
      end
      retval = "$#{escape}$#{password}$#{escape}$"
    end
    retval 
  end
end
