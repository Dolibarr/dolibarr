# hash a string as mysql's "PASSWORD()" function would do it
require 'digest/sha1'

module Puppet::Parser::Functions
  newfunction(:mysql_password, :type => :rvalue, :doc => <<-EOS
    Returns the mysql password hash from the clear text password.
    EOS
  ) do |args|

    raise(Puppet::ParseError, 'mysql_password(): Wrong number of arguments ' +
      "given (#{args.size} for 1)") if args.size != 1

    '*' + Digest::SHA1.hexdigest(Digest::SHA1.digest(args[0])).upcase
  end
end
