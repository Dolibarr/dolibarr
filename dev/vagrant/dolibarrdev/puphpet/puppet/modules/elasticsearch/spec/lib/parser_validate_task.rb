require 'find'
require 'pathname'
require 'rake'
require 'rspec/core/rake_task'

desc "run Puppet parser validate"
task :parser_validate do

  pwd = ENV["PWD"]
  puppet_file_paths = []
  Find.find(pwd) do |path|
    puppet_file_paths << path if path =~ /.*\.pp$/
  end

  exit_code = 0
  puppet_file_paths.each do |puppetfile|

    pwdpath = Pathname.new(pwd)
    pn = Pathname.new(puppetfile)
    rel_path = pn.relative_path_from(pwdpath)

    print "Validating #{rel_path}....  "
    $stdout.flush

    result = `puppet parser validate #{puppetfile}`
    if $?.exitstatus == 0
      res = 'OK'
    else
      res = 'ERR'
    end

    puts "#{res}"

    if $?.exitstatus != 0
      exit_code = 1
    end
  end
  exit exit_code

end
