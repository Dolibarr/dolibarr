# git_exec_path.rb
Facter.add('git_exec_path') do
  setcode 'git --exec-path 2>/dev/null'
end
