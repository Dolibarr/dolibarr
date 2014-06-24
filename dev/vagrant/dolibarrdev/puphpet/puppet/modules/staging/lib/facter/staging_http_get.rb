Facter.add("staging_http_get") do
  setcode do

    fact = nil

    which = lambda do |cmd|
      result = nil
      exts = ENV['PATHEXT'] ? ENV['PATHEXT'].split(';') : ['']
      ENV['PATH'].split(File::PATH_SEPARATOR).each do |path|
        exts.each do |ext|
          exe = File.join(path, "#{cmd}#{ext}")
          result = exe if File.executable? exe
          break if result
        end
        break if result
      end
      result
    end

    ['curl', 'wget', 'powershell'].each do |cmd|
      available = which.call(cmd)
      fact = available ? cmd : nil
      break if fact
    end

    fact

  end
end
