Puppet::Type.type(:file_line).provide(:ruby) do
  def exists?
    lines.find do |line|
      line.chomp == resource[:line].chomp
    end
  end

  def create
    if resource[:match]
      handle_create_with_match
    elsif resource[:after]
      handle_create_with_after
    else
      append_line
    end
  end

  def destroy
    local_lines = lines
    File.open(resource[:path],'w') do |fh|
      fh.write(local_lines.reject{|l| l.chomp == resource[:line] }.join(''))
    end
  end

  private
  def lines
    # If this type is ever used with very large files, we should
    #  write this in a different way, using a temp
    #  file; for now assuming that this type is only used on
    #  small-ish config files that can fit into memory without
    #  too much trouble.
    @lines ||= File.readlines(resource[:path])
  end

  def handle_create_with_match()
    regex = resource[:match] ? Regexp.new(resource[:match]) : nil
    match_count = lines.select { |l| regex.match(l) }.size
    if match_count > 1 && resource[:multiple].to_s != 'true'
     raise Puppet::Error, "More than one line in file '#{resource[:path]}' matches pattern '#{resource[:match]}'"
    end
    File.open(resource[:path], 'w') do |fh|
      lines.each do |l|
        fh.puts(regex.match(l) ? resource[:line] : l)
      end

      if (match_count == 0)
        fh.puts(resource[:line])
      end
    end
  end

  def handle_create_with_after
    regex = Regexp.new(resource[:after])

    count = lines.count {|l| l.match(regex)}

    case count
    when 1 # find the line to put our line after
      File.open(resource[:path], 'w') do |fh|
        lines.each do |l|
          fh.puts(l)
          if regex.match(l) then
            fh.puts(resource[:line])
          end
        end
      end
    when 0 # append the line to the end of the file
      append_line
    else
      raise Puppet::Error, "#{count} lines match pattern '#{resource[:after]}' in file '#{resource[:path]}'.  One or no line must match the pattern."
    end
  end

  ##
  # append the line to the file.
  #
  # @api private
  def append_line
    File.open(resource[:path], 'a') do |fh|
      fh.puts resource[:line]
    end
  end
end
