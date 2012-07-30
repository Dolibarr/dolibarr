#
# jQuery File Tree Ruby Connector
#
# Version 1.01
#
# Erik Lax
# http://datahack.se
# 13 July 2008
#
# History
#
# 1.01 Initial Release
#
# Output a list of files for jQuery File Tree
#

#<settings>
#root = "/absolute/path/"
# or
root = File.expand_path(".")
#</settings>

#<code>
require "cgi"
cgi = CGI.new
cgi.header("type" => "text/html")
dir = cgi.params["dir"].to_s

puts "<ul class=\"jqueryFileTree\" style=\"display: none;\">"
begin
	path = root + "/" + dir 

	# chdir() to user requested dir (root + "/" + dir) 
	Dir.chdir(File.expand_path(path).untaint);
	
	# check that our base path still begins with root path
	if Dir.pwd[0,root.length] == root then

		#loop through all directories
		Dir.glob("*") {
			|x|
			if not File.directory?(x.untaint) then next end 
			puts "<li class=\"directory collapsed\"><a href=\"#\" rel=\"#{dir}#{x}/\">#{x}</a></li>";
		}

		#loop through all files
		Dir.glob("*") {
			|x|
			if not File.file?(x.untaint) then next end 
			ext = File.extname(x)[1..-1]
			puts "<li class=\"file ext_#{ext}\"><a href=\"#\" rel=\"#{dir}#{x}\">#{x}</a></li>"
		}
	else
		#only happens when someone tries to go outside your root directory...
		puts "You are way out of your league"
	end 
rescue 
	puts "Internal Error"
end
puts "</ul>"
#</code>
