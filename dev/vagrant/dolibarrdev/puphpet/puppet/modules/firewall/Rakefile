require 'puppetlabs_spec_helper/rake_tasks'

require 'puppet-lint/tasks/puppet-lint'
PuppetLint.configuration.ignore_paths = ['vendor/**/*.pp']

task :default do
  sh %{rake -T}
end

desc 'Run reasonably quick tests for CI'
task :ci => [
  :lint,
  :spec,
]
