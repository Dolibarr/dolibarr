require 'uri'

Puppet::Parser::Functions::newfunction(:url_parse, :type => :rvalue, :doc => <<-'ENDHEREDOC') do |args|
    Returns information about an url
    
    This function expects two arguments, an URL and the part of the url you want to retrieve. 

    Example:
    $source_filename = url_parse($source_url,path)

    Given an url like: https://my_user:my_pass@www.example.com:8080/path/to/file.php?id=1&ret=0
    You obtain the following results according to the second argument:
    scheme   : https
    userinfo : my_user:my_pass
    user     : my_user
    password : my_pass
    host     : www.example.com
    port     : 8080
    path     : /path/to/file.php
    query    : id=1&ret=0
    filename : file.php
    filetype : php
    filedir  : file
   
 
  ENDHEREDOC
  raise ArgumentError, ("url_parse(): wrong number of arguments (#{args.length}; must be 2)") if args.length != 2
  url=URI.parse args[0]
  case args[1]
    when 'scheme' then url.scheme
    when 'userinfo' then url.userinfo
    when 'user' then url.user
    when 'password' then url.password
    when 'host' then url.host
    when 'port' then url.port
    when 'path' then url.path
    when 'query' then url.query
    when 'filename' then File.basename url.path
    when 'filetype' then File.extname url.path
    when 'filedir' then (File.basename url.path).chomp(File.extname(url.path))
    else url
  end
end

