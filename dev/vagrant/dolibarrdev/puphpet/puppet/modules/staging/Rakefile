require 'rubygems'
require 'puppetlabs_spec_helper/rake_tasks'

def io_popen(command)
  IO.popen(command) do |io|
    io.each do |line|
      print line
      yield line if block_given?
    end
  end
end

# Customize lint option
task :lint do
  PuppetLint.configuration.send("disable_80chars")
  PuppetLint.configuration.send("disable_class_parameter_defaults")
end

desc "Validate manifests, templates, and ruby files in lib."
task :validate do
  Dir['manifests/**/*.pp'].each do |manifest|
    sh "puppet parser validate --noop #{manifest}"
  end
  Dir['lib/**/*.rb'].each do |lib_file|
    sh "ruby -c #{lib_file}"
  end
  Dir['templates/**/*.erb'].each do |template|
    sh "erb -P -x -T '-' #{template} | ruby -c"
  end
end

# Initialize vagrant instance for testing
desc "Powers on Vagrant VMs with specific manifests"
task :vagrant, :manifest do |t, args|
  Rake::Task["spec_prep"].execute

  prefix = "VAGRANT_MANIFEST='#{args[:manifest]||'init.pp'}'"

  puts args[:manifest]
  provision = false
  io_popen("export #{prefix}; vagrant up --provider=vmware_fusion") do |line|
    provision = true if line =~ /Machine is already running./
  end
  io_popen("export #{prefix}; vagrant provision") if provision
end

# Cleanup vagrant environment
task :vagrant_clean do
  `vagrant destroy -f`
  Rake::Task["spec_clean"].execute
end
