# git_version
Facter.add('git_version') do
  setcode 'git --version 2>/dev/null'.sub(/git version /, '')
end
