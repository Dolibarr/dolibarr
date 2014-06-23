require 'puppet'
require 'set'
Puppet::Type.type(:rabbitmq_user).provide(:rabbitmqctl) do

  if Puppet::PUPPETVERSION.to_f < 3
    commands :rabbitmqctl => 'rabbitmqctl'
  else
     has_command(:rabbitmqctl, 'rabbitmqctl') do
       environment :HOME => "/tmp"
     end
  end

  defaultfor :feature => :posix

  def self.instances
    rabbitmqctl('list_users').split(/\n/)[1..-2].collect do |line|
      if line =~ /^(\S+)(\s+\[.*?\]|)$/
        new(:name => $1)
      else
        raise Puppet::Error, "Cannot parse invalid user line: #{line}"
      end
    end
  end

  def create
    rabbitmqctl('add_user', resource[:name], resource[:password])
    if resource[:admin] == :true
      make_user_admin()
    end
  end

  def destroy
    rabbitmqctl('delete_user', resource[:name])
  end

  def exists?
    rabbitmqctl('list_users').split(/\n/)[1..-2].detect do |line|
      line.match(/^#{Regexp.escape(resource[:name])}(\s+(\[.*?\]|\S+)|)$/)
    end
  end

  # def password
  # def password=()
  def admin
    if usertags = get_user_tags
      (:true if usertags.include?('administrator')) || :false
    else
      raise Puppet::Error, "Could not match line '#{resource[:name]} (true|false)' from list_users (perhaps you are running on an older version of rabbitmq that does not support admin users?)"
    end
  end


  def admin=(state)
    if state == :true
      make_user_admin()
    else
      usertags = get_user_tags
      usertags.delete('administrator')
      rabbitmqctl('set_user_tags', resource[:name], usertags.entries.sort)
    end
  end

  def make_user_admin
    usertags = get_user_tags
    usertags.add('administrator')
    rabbitmqctl('set_user_tags', resource[:name], usertags.entries.sort)
  end

  private
  def get_user_tags
    match = rabbitmqctl('list_users').split(/\n/)[1..-2].collect do |line|
      line.match(/^#{Regexp.escape(resource[:name])}\s+\[(.*?)\]/)
    end.compact.first
    Set.new(match[1].split(/, /)) if match
  end

end
