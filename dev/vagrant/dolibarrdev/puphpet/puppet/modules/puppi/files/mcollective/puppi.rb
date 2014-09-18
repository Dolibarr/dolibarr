module MCollective
    module Agent
        class Puppi<RPC::Agent
            metadata    :name        => "SimpleRPC Agent For PUPPI Commands",
                        :description => "Agent to execute PUPPI actions via MCollective",
                        :author      => "Al @ Lab42",
                        :license     => "Apache License 2.0",
                        :version     => "0.3",
                        :url         => "http://www.example42.com/",
                        :timeout     => 600

            def check_action
#                   validate :project, :shellsafe
                    project = request[:project] if request[:project]
                    reply.data = %x[puppi check #{project}].chomp
                    if ($?.exitstatus > 0)
                      reply.fail "FAILED: #{reply.data}"
                    end
            end

            def info_action
#                   validate :project, :shellsafe
                    project = request[:project] if request[:project]
                    reply.data = %x[puppi info #{project}].chomp
                    if ($?.exitstatus > 0)
                      reply.fail "FAILED: #{reply.data}"
                    end
            end

            def log_action
#                   validate :project, :shellsafe
                    project = request[:project] if request[:project]
                    reply.data = %x[puppi log #{project} -c 10].chomp
                    if ($?.exitstatus > 0)
                      reply.fail "FAILED: #{reply.data}"
                    end
            end

            def deploy_action
                    validate :project, :shellsafe
                    project = request[:project] if request[:project]
                    if (!File.directory? "/etc/puppi/projects/#{project}")
                      reply.fail "No such project #{project}"
                      return
                    end
                    puppioptions = request[:puppioptions]
                    reply.data = %x[puppi deploy #{project} -o "#{puppioptions}"].chomp
                    if ($?.exitstatus > 0)
                      reply.fail "FAILED: #{reply.data}"
                    end
            end

            def rollback_action
                    validate :project, :shellsafe
                    project = request[:project] if request[:project]
                    reply.data = %x[puppi rollback #{project} latest].chomp
                    if ($?.exitstatus > 0)
                      reply.fail "FAILED: #{reply.data}"
                    end
            end

            def init_action
                    validate :project, :shellsafe
                    project = request[:project] if request[:project]
                    reply.data = %x[puppi init #{project}].chomp
                    if ($?.exitstatus > 0)
                      reply.fail "FAILED: #{reply.data}"
                    end
            end
            
            def configure_action
                  validate :project, :shellsafe
                  project = request[:project] if request[:project]
                  reply.data = %x[puppi configure #{project}].chomp
                  if ($?.exitstatus > 0)
                    reply.fail "FAILED: #{reply.data}"
                  end
            end

        end
    end
end
# vi:tabstop=4:expandtab:ai
