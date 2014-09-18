require 'spec_helper'

describe 'rabbitmq' do

  context 'on unsupported distributions' do
    let(:facts) {{ :osfamily => 'Unsupported' }}

    it 'we fail' do
      expect { subject }.to raise_error(/not supported on an Unsupported/)
    end
  end

  context 'on Debian' do
    let(:facts) {{ :osfamily => 'Debian', :lsbdistid => 'Debian', :lsbdistcodename => 'squeeze' }}
    it 'includes rabbitmq::repo::apt' do
      should contain_class('rabbitmq::repo::apt')
    end

    describe 'apt::source default values' do
      let(:facts) {{ :osfamily => 'Debian' }}
      it 'should add a repo with defaults values' do
        contain_file('/etc/apt/sources.list.d/rabbitmq.list')\
          .with_content(%r|deb http\://www\.rabbitmq.com/debian/ testing main|)
      end
    end

    describe 'apt::source custom values' do
      let(:params) {
        { :location => 'http://www.foorepo.com/debian',
          :release => 'unstable',
          :repos => 'main'
        }}
      it 'should add a repo with custom new values' do
        contain_file('/etc/apt/sources.list.d/rabbitmq.list')\
          .with_content(%r|deb http\://www\.foorepo.com/debian/ unstable main|)
      end
    end
  end

  context 'on Debian' do
    let(:params) {{ :manage_repos => false }}
    let(:facts) {{ :osfamily => 'Debian', :lsbdistid => 'Debian', :lsbdistcodename => 'squeeze' }}
    it 'does not include rabbitmq::repo::apt when manage_repos is false' do
      should_not contain_class('rabbitmq::repo::apt')
    end
  end

  context 'on Redhat' do
    let(:facts) {{ :osfamily => 'RedHat' }}
    it 'includes rabbitmq::repo::rhel' do
      should contain_class('rabbitmq::repo::rhel')
    end
  end

  context 'on Redhat' do
    let(:params) {{ :manage_repos => false }}
    let(:facts) {{ :osfamily => 'RedHat' }}
    it 'does not include rabbitmq::repo::rhel when manage_repos is false' do
      should_not contain_class('rabbitmq::repo::rhel')
    end
  end

  ['Debian', 'RedHat', 'SUSE', 'Archlinux'].each do |distro|
    context "on #{distro}" do
      let(:facts) {{
        :osfamily => distro,
        :rabbitmq_erlang_cookie => 'EOKOWXQREETZSHFNTPEY',
        :lsbdistcodename => 'squeeze',
        :lsbdistid => 'Debian'
      }}

      it { should contain_class('rabbitmq::install') }
      it { should contain_class('rabbitmq::config') }
      it { should contain_class('rabbitmq::service') }


      context 'with admin_enable set to true' do
        let(:params) {{ :admin_enable => true }}
        context 'with service_manage set to true' do
          it 'we enable the admin interface by default' do
            should contain_class('rabbitmq::install::rabbitmqadmin')
            should contain_rabbitmq_plugin('rabbitmq_management').with(
              'require' => 'Class[Rabbitmq::Install]',
              'notify'  => 'Class[Rabbitmq::Service]'
            )
          end
        end
        context 'with service_manage set to false' do
          let(:params) {{ :admin_enable => true, :service_manage => false }}
          it 'should do nothing' do
            should_not contain_class('rabbitmq::install::rabbitmqadmin')
            should_not contain_rabbitmq_plugin('rabbitmq_management')
          end
        end
      end

      context 'deprecated parameters' do
        describe 'cluster_disk_nodes' do
          let(:params) {{ :cluster_disk_nodes => ['node1', 'node2'] }}

          it { should contain_notify('cluster_disk_nodes') }
        end
      end

      describe 'manages configuration directory correctly' do
        it { should contain_file('/etc/rabbitmq').with(
          'ensure' => 'directory'
        )}
      end

      describe 'manages configuration file correctly' do
        it { should contain_file('rabbitmq.config') }
      end

      context 'configures config_cluster' do
        let(:facts) {{ :osfamily => distro, :rabbitmq_erlang_cookie => 'ORIGINAL', :lsbdistid => 'Debian' }}
        let(:params) {{
          :config_cluster           => true,
          :cluster_nodes            => ['hare-1', 'hare-2'],
          :cluster_node_type        => 'ram',
          :erlang_cookie            => 'TESTCOOKIE',
          :wipe_db_on_cookie_change => false
        }}

        describe 'with defaults' do
          it 'fails' do
            expect{subject}.to raise_error(/^ERROR: The current erlang cookie is ORIGINAL/)
          end
        end

        describe 'with wipe_db_on_cookie_change set' do
          let(:params) {{
            :config_cluster           => true,
            :cluster_nodes            => ['hare-1', 'hare-2'],
            :cluster_node_type        => 'ram',
            :erlang_cookie            => 'TESTCOOKIE',
            :wipe_db_on_cookie_change => true
          }}
          it 'wipes the database' do
            should contain_exec('wipe_db')
            should contain_file('erlang_cookie')
          end
        end

        describe 'correctly when cookies match' do
          let(:params) {{
            :config_cluster           => true,
            :cluster_nodes            => ['hare-1', 'hare-2'],
            :cluster_node_type        => 'ram',
            :erlang_cookie            => 'ORIGINAL',
            :wipe_db_on_cookie_change => true
          }}
          it 'and doesnt wipe anything' do
            should contain_file('erlang_cookie')
          end
        end

        describe 'and sets appropriate configuration' do
          let(:params) {{
            :config_cluster           => true,
            :cluster_nodes            => ['hare-1', 'hare-2'],
            :cluster_node_type        => 'ram',
            :erlang_cookie            => 'ORIGINAL',
            :wipe_db_on_cookie_change => true
          }}
          it 'for cluster_nodes' do
            should contain_file('rabbitmq.config').with({
              'content' => /cluster_nodes.*\['rabbit@hare-1', 'rabbit@hare-2'\], ram/,
            })
          end

          it 'for erlang_cookie' do
            should contain_file('erlang_cookie').with({
              'content' => 'ORIGINAL',
            })
          end
        end
      end

      describe 'rabbitmq-env configuration' do
        let(:params) {{ :environment_variables => {
          'RABBITMQ_NODE_IP_ADDRESS'    => '1.1.1.1',
          'RABBITMQ_NODE_PORT'          => '5656',
          'RABBITMQ_NODENAME'           => 'HOSTNAME',
          'RABBITMQ_SERVICENAME'        => 'RabbitMQ',
          'RABBITMQ_CONSOLE_LOG'        => 'RabbitMQ.debug',
          'RABBITMQ_CTL_ERL_ARGS'       => 'verbose',
          'RABBITMQ_SERVER_ERL_ARGS'    => 'v',
          'RABBITMQ_SERVER_START_ARGS'  => 'debug'
        }}}
        it 'should set environment variables' do
          should contain_file('rabbitmq-env.config') \
            .with_content(/RABBITMQ_NODE_IP_ADDRESS=1.1.1.1/) \
            .with_content(/RABBITMQ_NODE_PORT=5656/) \
            .with_content(/RABBITMQ_NODENAME=HOSTNAME/) \
            .with_content(/RABBITMQ_SERVICENAME=RabbitMQ/) \
            .with_content(/RABBITMQ_CONSOLE_LOG=RabbitMQ.debug/) \
            .with_content(/RABBITMQ_CTL_ERL_ARGS=verbose/) \
            .with_content(/RABBITMQ_SERVER_ERL_ARGS=v/) \
            .with_content(/RABBITMQ_SERVER_START_ARGS=debug/)
        end
      end

      context 'delete_guest_user' do
        describe 'should do nothing by default' do
          it { should_not contain_rabbitmq_user('guest') }
        end

        describe 'delete user when delete_guest_user set' do
          let(:params) {{ :delete_guest_user => true }}
          it 'removes the user' do
            should contain_rabbitmq_user('guest').with(
              'ensure'   => 'absent',
              'provider' => 'rabbitmqctl'
            )
          end
        end
      end

      context 'configuration setting' do
        describe 'node_ip_address when set' do
          let(:params) {{ :node_ip_address => '172.0.0.1' }}
          it 'should set RABBITMQ_NODE_IP_ADDRESS to specified value' do
            contain_file('rabbitmq-env.config').with({
              'content' => 'RABBITMQ_NODE_IP_ADDRESS=172.0.0.1',
            })
          end
        end

        describe 'stomp by default' do
          it 'should not specify stomp parameters in rabbitmq.config' do
            contain_file('rabbitmq.config').without({
              'content' => /stomp/,})
          end
        end
        describe 'stomp when set' do
          let(:params) {{ :config_stomp => true, :stomp_port => 5679 }}
          it 'should specify stomp port in rabbitmq.config' do
            contain_file('rabbitmq.config').with({
              'content' => /rabbitmq_stomp.*tcp_listeners, \[5679\]/,
            })
          end
        end
        describe 'stomp when set with ssl' do
          let(:params) {{ :config_stomp => true, :stomp_port => 5679, :ssl_stomp_port => 5680 }}
          it 'should specify stomp port and ssl stomp port in rabbitmq.config' do
            contain_file('rabbitmq.config').with({
              'content' => /rabbitmq_stomp.*tcp_listeners, \[5679\].*ssl_listeners, \[5680\]/,
            })
          end
        end
      end

      describe 'configuring ldap authentication' do
        let :params do
          { :config_stomp         => true,
            :ldap_auth            => true,
            :ldap_server          => 'ldap.example.com',
            :ldap_user_dn_pattern => 'ou=users,dc=example,dc=com',
            :ldap_use_ssl         => false,
            :ldap_port            => '389',
            :ldap_log             => true
          }
        end

        it { should contain_rabbitmq_plugin('rabbitmq_auth_backend_ldap') }

        it 'should contain ldap parameters' do
          verify_contents(subject, 'rabbitmq.config', 
                          ['[', '  {rabbit, [', '    {auth_backends, [rabbit_auth_backend_internal, rabbit_auth_backend_ldap]},', '  ]}',
                            '  {rabbitmq_auth_backend_ldap, [', '    {other_bind, anon},',
                            '    {servers, ["ldap.example.com"]},',
                            '    {user_dn_pattern, "ou=users,dc=example,dc=com"},', '    {use_ssl, false},',
                            '    {port, 389},', '    {log, true}'])
        end
      end

      describe 'configuring ldap authentication' do
        let :params do
          { :config_stomp         => false,
            :ldap_auth            => true,
            :ldap_server          => 'ldap.example.com',
            :ldap_user_dn_pattern => 'ou=users,dc=example,dc=com',
            :ldap_use_ssl         => false,
            :ldap_port            => '389',
            :ldap_log             => true
          }
        end

        it { should contain_rabbitmq_plugin('rabbitmq_auth_backend_ldap') }

        it 'should contain ldap parameters' do
          verify_contents(subject, 'rabbitmq.config', 
                          ['[', '  {rabbit, [', '    {auth_backends, [rabbit_auth_backend_internal, rabbit_auth_backend_ldap]},', '  ]}',
                            '  {rabbitmq_auth_backend_ldap, [', '    {other_bind, anon},',
                            '    {servers, ["ldap.example.com"]},',
                            '    {user_dn_pattern, "ou=users,dc=example,dc=com"},', '    {use_ssl, false},',
                            '    {port, 389},', '    {log, true}'])
        end
      end

      describe 'default_user and default_pass set' do
        let(:params) {{ :default_user => 'foo', :default_pass => 'bar' }}
        it 'should set default_user and default_pass to specified values' do
          contain_file('rabbitmq.config').with({
            'content' => /default_user, <<"foo">>.*default_pass, <<"bar">>/,
          })
        end
      end

      describe 'ssl options' do
        let(:params) {
          { :ssl => true,
            :ssl_management_port => 3141,
            :ssl_cacert => '/path/to/cacert',
            :ssl_cert => '/path/to/cert',
            :ssl_key => '/path/to/key'
        } }

        it 'should set ssl options to specified values' do
          contain_file('rabbitmq.config').with({
            'content' => %r|ssl_listeners, \[3141\].*
            ssl_options, \[{cacertfile,"/path/to/cacert".*
            certfile="/path/to/cert".*
            keyfile,"/path/to/key|,
          })
        end
      end

      describe 'ssl options with ssl_only' do
        let(:params) {
          { :ssl => true,
            :ssl_only => true,
            :ssl_management_port => 3141,
            :ssl_cacert => '/path/to/cacert',
            :ssl_cert => '/path/to/cert',
            :ssl_key => '/path/to/key'
        } }

        it 'should set ssl options to specified values' do
          contain_file('rabbitmq.config').with({
            'content' => %r|tcp_listeners, \[\].*
            ssl_listeners, \[3141\].*
            ssl_options, \[{cacertfile,"/path/to/cacert".*
            certfile="/path/to/cert".*
            keyfile,"/path/to/key|,
          })
        end
      end

      describe 'config_variables options' do
        let(:params) {{ :config_variables => {
            'hipe_compile'                  => true,
            'vm_memory_high_watermark'      => 0.4,
            'frame_max'                     => 131072,
            'collect_statistics'            => "none",
            'auth_mechanisms'               => "['PLAIN', 'AMQPLAIN']",
        }}}
        it 'should set environment variables' do
          should contain_file('rabbitmq.config') \
            .with_content(/\{hipe_compile, true\}/) \
            .with_content(/\{vm_memory_high_watermark, 0.4\}/) \
            .with_content(/\{frame_max, 131072\}/) \
            .with_content(/\{collect_statistics, none\}/) \
            .with_content(/\{auth_mechanisms, \['PLAIN', 'AMQPLAIN'\]\}/)
        end
      end

      describe 'config_kernel_variables options' do
        let(:params) {{ :config_kernel_variables => {
            'inet_dist_listen_min'      => 9100,
            'inet_dist_listen_max'      => 9105,
        }}}
        it 'should set config variables' do
          should contain_file('rabbitmq.config') \
            .with_content(/\{inet_dist_listen_min, 9100\}/) \
            .with_content(/\{inet_dist_listen_max, 9105\}/) 
        end
      end

      context 'delete_guest_user' do
        describe 'should do nothing by default' do
          it { should_not contain_rabbitmq_user('guest') }
        end

        describe 'delete user when delete_guest_user set' do
          let(:params) {{ :delete_guest_user => true }}
          it 'removes the user' do
            should contain_rabbitmq_user('guest').with(
              'ensure'   => 'absent',
              'provider' => 'rabbitmqctl'
            )
          end
        end
      end

      ##
      ## rabbitmq::service
      ##
      describe 'service with default params' do
        it { should contain_service('rabbitmq-server').with(
          'ensure'     => 'running',
          'enable'     => 'true',
          'hasstatus'  => 'true',
          'hasrestart' => 'true'
        )}
      end

      describe 'service with ensure stopped' do
        let :params do
          { :service_ensure => 'stopped' }
        end

        it { should contain_service('rabbitmq-server').with(
          'ensure'    => 'stopped',
          'enable'    => false
        ) }
      end

      describe 'service with ensure neither running neither stopped' do
        let :params do
          { :service_ensure => 'foo' }
        end

        it 'should raise an error' do
          expect {
            should contain_service('rabbitmq-server').with(
              'ensure' => 'stopped' )
          }.to raise_error(Puppet::Error, /validate_re\(\): "foo" does not match "\^\(running\|stopped\)\$"/)
        end
      end

      describe 'service with service_manage equal to false' do
        let :params do
          { :service_manage => false }
        end

        it { should_not contain_service('rabbitmq-server') }
      end

    end
  end

  ##
  ## rabbitmq::install
  ##
  context "on RHEL" do
    let(:facts) {{ :osfamily => 'RedHat' }}
    let(:params) {{ :package_source => 'http://www.rabbitmq.com/releases/rabbitmq-server/v3.2.3/rabbitmq-server-3.2.3-1.noarch.rpm' }}
    it 'installs the rabbitmq package' do
      should contain_package('rabbitmq-server').with(
        'ensure'   => 'installed',
        'name'     => 'rabbitmq-server',
        'provider' => 'yum',
        'source'   => 'http://www.rabbitmq.com/releases/rabbitmq-server/v3.2.3/rabbitmq-server-3.2.3-1.noarch.rpm'
      )
    end
  end

  context "on Debian" do
    let(:facts) {{ :osfamily => 'Debian', :lsbdistid => 'Debian', :lsbdistcodename => 'precise' }}
    it 'installs the rabbitmq package' do
      should contain_package('rabbitmq-server').with(
        'ensure'   => 'installed',
        'name'     => 'rabbitmq-server',
        'provider' => 'apt'
      )
    end
  end

  context "on Archlinux" do
    let(:facts) {{ :osfamily => 'Archlinux' }}
    it 'installs the rabbitmq package' do
      should contain_package('rabbitmq-server').with(
        'ensure'   => 'installed',
        'name'     => 'rabbitmq')
    end
  end

  describe 'repo management on Debian' do
    let(:facts)  {{ :osfamily => 'Debian', :lsbdistid => 'Debian' }}

    context 'with no pin' do
      let(:params) {{ :package_apt_pin => '' }}
      describe 'it sets up an apt::source' do

        it { should contain_apt__source('rabbitmq').with(
          'location'    => 'http://www.rabbitmq.com/debian/',
          'release'     => 'testing',
          'repos'       => 'main',
          'include_src' => false,
          'key'         => '056E8E56'
        ) }
      end
    end

    context 'with pin' do
      let(:params) {{ :package_apt_pin => '700' }}
      describe 'it sets up an apt::source and pin' do

        it { should contain_apt__source('rabbitmq').with(
          'location'    => 'http://www.rabbitmq.com/debian/',
          'release'     => 'testing',
          'repos'       => 'main',
          'include_src' => false,
          'key'         => '056E8E56'
        ) }

        it { should contain_apt__pin('rabbitmq').with(
          'packages' => 'rabbitmq-server',
          'priority' => '700'
        ) }

      end
    end
  end

  ['RedHat', 'SuSE'].each do |distro|
    describe "repo management on #{distro}" do
      describe 'imports the key' do
        let(:facts) {{ :osfamily => distro }}
        let(:params) {{ :package_gpg_key => 'http://www.rabbitmq.com/rabbitmq-signing-key-public.asc' }}

        it { should contain_exec("rpm --import #{params[:package_gpg_key]}").with(
          'path' => ['/bin','/usr/bin','/sbin','/usr/sbin']
        ) }
      end
    end
  end

end
