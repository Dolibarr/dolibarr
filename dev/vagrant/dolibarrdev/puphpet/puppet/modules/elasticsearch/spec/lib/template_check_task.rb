require 'find'
require 'pathname'
require 'rake'
require 'rspec/core/rake_task'

desc "Verify puppet templates"
task :template_verify do

  pwd = ENV["PWD"]
  erb_file_paths = []
  Find.find(pwd) do |path|
    erb_file_paths << path if path =~ /.*\.erb$/
  end

  exit_code = 0
  erb_file_paths.each do |erbfile|

    pwdpath = Pathname.new(pwd)
    pn = Pathname.new(erbfile)
    rel_path = pn.relative_path_from(pwdpath)

    result = `erb -P -x -T '-' #{erbfile} | ruby -c`
    puts "Verifying #{rel_path}.... #{result}"

    if $?.exitstatus != 0
      exit_code = 1
    end
  end
  exit exit_code

end
