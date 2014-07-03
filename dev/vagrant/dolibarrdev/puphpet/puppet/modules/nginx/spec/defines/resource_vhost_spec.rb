require 'spec_helper'

describe 'nginx::resource::vhost' do
  let :title do
    'www.rspec.example.com'
  end
  let :default_params do
    {
      :www_root    => '/',
      :ipv6_enable => true,
    }
  end
  let :facts do
    {
      :osfamily        => 'Debian',
      :operatingsystem => 'debian',
      :ipaddress6      => '::',
    }
  end
  let :pre_condition do
    [
      'include ::nginx::params',
      'include ::nginx::config',
    ]
  end

  describe 'os-independent items' do

    describe 'basic assumptions' do
      let :params do default_params end
      it { should contain_class("nginx::params") }
      it { should contain_class("nginx::config") }
      it { should contain_concat("/etc/nginx/sites-available/#{title}.conf").with({
        'owner' => 'root',
        'group' => 'root',
        'mode'  => '0644',
      })}
      it { should contain_concat__fragment("#{title}-header").with_content(%r{access_log[ ]+/var/log/nginx/www\.rspec\.example\.com\.access\.log}) }
      it { should contain_concat__fragment("#{title}-header").with_content(%r{error_log[ ]+/var/log/nginx/www\.rspec\.example\.com\.error\.log}) }
      it { should contain_concat__fragment("#{title}-footer") }
      it { should contain_nginx__resource__location("#{title}-default") }
      it { should_not contain_file("/etc/nginx/fastcgi_params") }
      it { should contain_file("#{title}.conf symlink").with({
        'ensure' => 'link',
        'path'   => "/etc/nginx/sites-enabled/#{title}.conf",
        'target' => "/etc/nginx/sites-available/#{title}.conf"
      })}
    end

    describe "vhost_header template content" do
      [
        {
          :title => 'should set the IPv4 listen IP',
          :attr  => 'listen_ip',
          :value => '127.0.0.1',
          :match => '  listen                127.0.0.1:80;',
        },
        {
          :title => 'should set the IPv4 listen port',
          :attr  => 'listen_port',
          :value => 45,
          :match => '  listen                *:45;',
        },
        {
          :title => 'should set the IPv4 listen options',
          :attr  => 'listen_options',
          :value => 'spdy default',
          :match => '  listen                *:80 spdy default;',
        },
        {
          :title => 'should enable IPv6',
          :attr  => 'ipv6_enable',
          :value => true,
          :match => '  listen [::]:80 default ipv6only=on;',
        },
        {
          :title    => 'should not enable IPv6',
          :attr     => 'ipv6_enable',
          :value    => false,
          :notmatch => /  listen \[::\]:80 default ipv6only=on;/,
        },
        {
          :title => 'should set the IPv6 listen IP',
          :attr  => 'ipv6_listen_ip',
          :value => '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
          :match => '  listen [2001:0db8:85a3:0000:0000:8a2e:0370:7334]:80 default ipv6only=on;',
        },
        {
          :title => 'should set the IPv6 listen port',
          :attr  => 'ipv6_listen_port',
          :value => 45,
          :match => '  listen [::]:45 default ipv6only=on;',
        },
        {
          :title => 'should set the IPv6 listen options',
          :attr  => 'ipv6_listen_options',
          :value => 'spdy',
          :match => '  listen [::]:80 spdy;',
        },
        {
          :title => 'should set servername(s)',
          :attr  => 'server_name',
          :value => ['name1','name2'],
          :match => '  server_name           name1 name2;',
        },
        {
          :title => 'should rewrite www servername to non-www',
          :attr  => 'rewrite_www_to_non_www',
          :value => true,
          :match => '  server_name           rspec.example.com;',
        },
        {
          :title => 'should not rewrite www servername to non-www',
          :attr  => 'rewrite_www_to_non_www',
          :value => false,
          :match => '  server_name           www.rspec.example.com;',
        },
        {
          :title => 'should set auth_basic',
          :attr  => 'auth_basic',
          :value => 'value',
          :match => '  auth_basic           "value";',
        },
        {
          :title => 'should set auth_basic_user_file',
          :attr  => 'auth_basic_user_file',
          :value => 'value',
          :match => '  auth_basic_user_file value;',
        },
        {
          :title => 'should contain ordered prepended directives',
          :attr  => 'vhost_cfg_prepend',
          :value => { 'test1' => ['test value 1a', 'test value 1b'], 'test2' => 'test value 2', 'allow' => 'test value 3' },
          :match => [
            '  allow test value 3;',
            '  test1 test value 1a;',
            '  test1 test value 1b;',
            '  test2 test value 2;',
          ],
        },
        {
          :title => 'should set root',
          :attr  => 'use_default_location',
          :value => false,
          :match => '  root /;',
        },
        {
          :title    => 'should not set root',
          :attr     => 'use_default_location',
          :value    => true,
          :notmatch => /  root \/;/,
        },
        {
          :title => 'should set proxy_set_header',
          :attr  => 'proxy_set_header',
          :value => ['header1','header2'],
          :match => [
            '  proxy_set_header        header1;',
            '  proxy_set_header        header2;',
          ],
        },
        {
          :title => 'should rewrite to HTTPS',
          :attr  => 'rewrite_to_https',
          :value => true,
          :match => [
            '  if ($ssl_protocol = "") {',
            '       return 301 https://$host$request_uri;',
          ],
        },
        {
          :title    => 'should not rewrite to HTTPS',
          :attr     => 'rewrite_to_https',
          :value    => false,
          :notmatch => [
            /if \(\$ssl_protocol = ""\) \{/,
            /       return 301 https:\/\/\$host\$request_uri;/,
          ],
        },
        {
          :title => 'should set access_log',
          :attr  => 'access_log',
          :value => '/path/to/access.log',
          :match => '  access_log            /path/to/access.log;',
        },
        {
          :title => 'should set error_log',
          :attr  => 'error_log',
          :value => '/path/to/error.log',
          :match => '  error_log             /path/to/error.log;',
        },
      ].each do |param|
        context "when #{param[:attr]} is #{param[:value]}" do
          let :params do default_params.merge({ param[:attr].to_sym => param[:value] }) end

          it { should contain_concat__fragment("#{title}-header") }
          it param[:title] do
            lines = subject.resource('concat::fragment', "#{title}-header").send(:parameters)[:content].split("\n")
            (lines & Array(param[:match])).should == Array(param[:match])
            Array(param[:notmatch]).each do |item|
              should contain_concat__fragment("#{title}-header").without_content(item)
            end
          end
        end
      end
    end

    describe "vhost_footer template content" do
      [
        {
          :title => 'should contain include directives',
          :attr  => 'include_files',
          :value => [ '/file1', '/file2' ],
          :match => [
            'include /file1;',
            'include /file2;',
          ],
        },
        {
          :title => 'should contain ordered appended directives',
          :attr  => 'vhost_cfg_append',
          :value => { 'test1' => 'test value 1', 'test2' => ['test value 2a', 'test value 2b'], 'allow' => 'test value 3' },
          :match => [
            '  allow test value 3;',
            '  test1 test value 1;',
            '  test2 test value 2a;',
            '  test2 test value 2b;',
          ],
        },
        {
          :title => 'should contain www to non-www rewrite',
          :attr  => 'rewrite_www_to_non_www',
          :value => true,
          :match => [
            '  listen                *:80;',
            '  server_name           www.rspec.example.com;',
            '  rewrite               ^ http://rspec.example.com$uri permanent;',
          ],
        },
        {
          :title    => 'should not contain www to non-www rewrite',
          :attr     => 'rewrite_www_to_non_www',
          :value    => false,
          :notmatch => [
            /  listen                \*:80;/,
            /  server_name           www\.rspec\.example\.com;/,
            /  rewrite               \^ http:\/\/rspec\.example\.com\$uri permanent;/,
          ],
        },
      ].each do |param|
        context "when #{param[:attr]} is #{param[:value]}" do
          let :params do default_params.merge({ param[:attr].to_sym => param[:value] }) end

          it { should contain_concat__fragment("#{title}-footer") }
          it param[:title] do
            lines = subject.resource('concat::fragment', "#{title}-footer").send(:parameters)[:content].split("\n")
            (lines & Array(param[:match])).should == Array(param[:match])
            Array(param[:notmatch]).each do |item|
              should contain_concat__fragment("#{title}-footer").without_content(item)
            end
          end
        end
      end
    end

    describe "vhost_ssl_header template content" do
      [
        {
          :title => 'should set the IPv4 listen IP',
          :attr  => 'listen_ip',
          :value => '127.0.0.1',
          :match => '  listen       127.0.0.1:443 ssl;',
        },
        {
          :title => 'should set the IPv4 SSL listen port',
          :attr  => 'ssl_port',
          :value => 45,
          :match => '  listen       *:45 ssl;',
        },
        {
          :title => 'should set SPDY',
          :attr  => 'spdy',
          :value => 'on',
          :match => '  listen       *:443 ssl spdy;',
        },
        {
          :title => 'should not set SPDY',
          :attr  => 'spdy',
          :value => 'off',
          :match => '  listen       *:443 ssl;',
        },
        {
          :title => 'should set the IPv4 listen options',
          :attr  => 'listen_options',
          :value => 'default',
          :match => '  listen       *:443 ssl default;',
        },
        {
          :title => 'should enable IPv6',
          :attr  => 'ipv6_enable',
          :value => true,
          :match => '  listen [::]:443 ssl default ipv6only=on;',
        },
        {
          :title    => 'should disable IPv6',
          :attr     => 'ipv6_enable',
          :value    => false,
          :notmatch => /  listen \[::\]:443 ssl default ipv6only=on;/,
        },
        {
          :title => 'should set the IPv6 listen IP',
          :attr  => 'ipv6_listen_ip',
          :value => '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
          :match => '  listen [2001:0db8:85a3:0000:0000:8a2e:0370:7334]:443 ssl default ipv6only=on;',
        },
        {
          :title => 'should set the IPv6 listen port',
          :attr  => 'ssl_port',
          :value => 45,
          :match => '  listen [::]:45 ssl default ipv6only=on;',
        },
        {
          :title => 'should set the IPv6 listen options',
          :attr  => 'ipv6_listen_options',
          :value => 'spdy default',
          :match => '  listen [::]:443 ssl spdy default;',
        },
        {
          :title => 'should set servername(s)',
          :attr  => 'server_name',
          :value => ['name1','name2'],
          :match => '  server_name  name1 name2;',
        },
        {
          :title => 'should rewrite www servername to non-www',
          :attr  => 'rewrite_www_to_non_www',
          :value => true,
          :match => '  server_name  rspec.example.com;',
        },
        {
          :title => 'should not rewrite www servername to non-www',
          :attr  => 'rewrite_www_to_non_www',
          :value => false,
          :match => '  server_name  www.rspec.example.com;',
        },
        {
          :title => 'should set the SSL cache',
          :attr  => 'ssl_cache',
          :value => 'shared:SSL:1m',
          :match => '  ssl_session_cache         shared:SSL:1m;',
        },
        {
          :title => 'should set the SSL protocols',
          :attr  => 'ssl_protocols',
          :value => 'SSLv3',
          :match => '  ssl_protocols             SSLv3;',
        },
        {
          :title => 'should set the SSL ciphers',
          :attr  => 'ssl_ciphers',
          :value => 'HIGH',
          :match => '  ssl_ciphers               HIGH;',
        },
        {
          :title => 'should set auth_basic',
          :attr  => 'auth_basic',
          :value => 'value',
          :match => '  auth_basic                "value";',
        },
        {
          :title => 'should set auth_basic_user_file',
          :attr  => 'auth_basic_user_file',
          :value => 'value',
          :match => '  auth_basic_user_file      "value";',
        },
        {
          :title => 'should set access_log',
          :attr  => 'access_log',
          :value => '/path/to/access.log',
          :match => '  access_log            /path/to/access.log;',
        },
        {
          :title => 'should set error_log',
          :attr  => 'error_log',
          :value => '/path/to/error.log',
          :match => '  error_log             /path/to/error.log;',
        },
        {
          :title => 'should contain ordered prepend directives',
          :attr  => 'vhost_cfg_prepend',
          :value => { 'test1' => 'test value 1', 'test2' => ['test value 2a', 'test value 2b'], 'allow' => 'test value 3' },
          :match => [
            '  allow test value 3;',
            '  test1 test value 1;',
            '  test2 test value 2a;',
            '  test2 test value 2b;',
          ]
        },
        {
          :title => 'should contain ordered ssl prepend directives',
          :attr  => 'vhost_cfg_ssl_prepend',
          :value => { 'test1' => 'test value 1', 'test2' => ['test value 2a', 'test value 2b'], 'allow' => 'test value 3' },
          :match => [
            '  allow test value 3;',
            '  test1 test value 1;',
            '  test2 test value 2a;',
            '  test2 test value 2b;',
          ]
        },
        {
          :title => 'should set root',
          :attr  => 'use_default_location',
          :value => false,
          :match => '  root /;',
        },
        {
          :title    => 'should not set root',
          :attr     => 'use_default_location',
          :value    => true,
          :notmatch => /  root \/;/,
        },
      ].each do |param|
        context "when #{param[:attr]} is #{param[:value]}" do
          let :params do default_params.merge({
            param[:attr].to_sym => param[:value],
            :ssl                => true,
            :ssl_key            => 'dummy.key',
            :ssl_cert           => 'dummy.crt',
          }) end
          it { should contain_concat__fragment("#{title}-ssl-header") }
          it param[:title] do
            lines = subject.resource('concat::fragment', "#{title}-ssl-header").send(:parameters)[:content].split("\n")
            (lines & Array(param[:match])).should == Array(param[:match])
            Array(param[:notmatch]).each do |item|
              should contain_concat__fragment("#{title}-ssl-header").without_content(item)
            end
          end
        end
      end
    end

    describe "vhost_ssl_footer template content" do
      [
        {
          :title => 'should contain include directives',
          :attr  => 'include_files',
          :value => [ '/file1', '/file2' ],
          :match => [
            'include /file1;',
            'include /file2;',
          ],
        },
        {
          :title => 'should contain ordered appended directives',
          :attr  => 'vhost_cfg_append',
          :value => { 'test1' => 'test value 1', 'test2' => 'test value 2', 'allow' => 'test value 3' },
          :match => [
            '  allow test value 3;',
            '  test1 test value 1;',
            '  test2 test value 2;',
          ]
        },
        {
          :title => 'should contain ordered ssl appended directives',
          :attr  => 'vhost_cfg_ssl_append',
          :value => { 'test1' => 'test value 1', 'test2' => ['test value 2a', 'test value 2b'], 'allow' => 'test value 3' },
          :match => [
            '  allow test value 3;',
            '  test1 test value 1;',
            '  test2 test value 2a;',
            '  test2 test value 2b;',
          ]
        },
        {
          :title => 'should contain www to non-www rewrite',
          :attr  => 'rewrite_www_to_non_www',
          :value => true,
          :match => [
            '  listen                *:443 ssl;',
            '  server_name           www.rspec.example.com;',
            '  rewrite               ^ https://rspec.example.com$uri permanent;',
          ],
        },
        {
          :title    => 'should not contain www to non-www rewrite',
          :attr     => 'rewrite_www_to_non_www',
          :value    => false,
          :notmatch => [
            /  listen                \*:443 ssl;/,
            /  server_name           www\.rspec\.example\.com;/,
            /  rewrite               \^ https:\/\/rspec\.example\.com\$uri permanent;/,
          ],
        },
      ].each do |param|
        context "when #{param[:attr]} is #{param[:value]}" do
          let :params do default_params.merge({
            param[:attr].to_sym => param[:value],
            :ssl                => true,
            :ssl_key            => 'dummy.key',
            :ssl_cert           => 'dummy.crt',
          }) end

          it { should contain_concat__fragment("#{title}-ssl-footer") }
          it param[:title] do
            lines = subject.resource('concat::fragment', "#{title}-ssl-footer").send(:parameters)[:content].split("\n")
            (lines & Array(param[:match])).should == Array(param[:match])
            Array(param[:notmatch]).each do |item|
              should contain_concat__fragment("#{title}-ssl-footer").without_content(item)
            end
          end
        end
      end
    end
    context 'attribute resources' do
      context "SSL cert missing" do
        let(:params) {{ :ssl => true, :ssl_key => 'key' }}

        it { expect { should contain_class('nginx::resource::vhost') }.to raise_error(Puppet::Error, %r{nginx: SSL certificate/key \(ssl_cert/ssl_cert\) and/or SSL Private must be defined and exist on the target system\(s\)}) }
      end

      context "SSL key missing" do
        let(:params) {{ :ssl => true, :ssl_cert => 'cert' }}

        it { expect { should contain_class('nginx::resource::vhost') }.to raise_error(Puppet::Error, %r{nginx: SSL certificate/key \(ssl_cert/ssl_cert\) and/or SSL Private must be defined and exist on the target system\(s\)}) }
      end

      context 'when use_default_location => true' do
        let :params do default_params.merge({
          :use_default_location => true,
        }) end

        it { should contain_nginx__resource__location("#{title}-default") }
      end

      context 'when use_default_location => false' do
        let :params do default_params.merge({
          :use_default_location => false,
        }) end

        it { should_not contain_nginx__resource__location("#{title}-default") }
      end

      context 'when location_cfg_prepend => { key => value }' do
        let :params do default_params.merge({
          :location_cfg_prepend => { 'key' => 'value' },
        }) end

        it { should contain_nginx__resource__location("#{title}-default").with_location_cfg_prepend({ 'key' => 'value' }) }
      end

      context 'when location_cfg_append => { key => value }' do
        let :params do default_params.merge({
          :location_cfg_append => { 'key' => 'value' },
        }) end

        it { should contain_nginx__resource__location("#{title}-default").with_location_cfg_append({ 'key' => 'value' }) }
      end

      context 'when fastcgi => "localhost:9000"' do
        let :params do default_params.merge({
          :fastcgi => 'localhost:9000',
        }) end

        it { should contain_file('/etc/nginx/fastcgi_params').with_mode('0770') }
      end

      context 'when listen_port == ssl_port' do
        let :params do default_params.merge({
          :listen_port => 80,
          :ssl_port    => 80,
        }) end

        it { should_not contain_concat__fragment("#{title}-header") }
        it { should_not contain_concat__fragment("#{title}-footer") }
      end

      context 'when listen_port != ssl_port' do
        let :params do default_params.merge({
          :listen_port => 80,
          :ssl_port    => 443,
        }) end

        it { should contain_concat__fragment("#{title}-header") }
        it { should contain_concat__fragment("#{title}-footer") }
      end

      context 'when ensure => absent' do
        let :params do default_params.merge({
          :ensure   => 'absent',
          :ssl      => true,
          :ssl_key  => 'dummy.key',
          :ssl_cert => 'dummy.cert',
        }) end

        it { should contain_nginx__resource__location("#{title}-default").with_ensure('absent') }
        it { should contain_file("#{title}.conf symlink").with_ensure('absent') }
      end

      context 'when ssl => true and ssl_port == listen_port' do
        let :params do default_params.merge({
          :ssl         => true,
          :listen_port => 80,
          :ssl_port    => 80,
          :ssl_key     => 'dummy.key',
          :ssl_cert    => 'dummy.cert',
        }) end

        it { should contain_nginx__resource__location("#{title}-default").with_ssl_only(true) }
        it { should contain_concat__fragment("#{title}-ssl-header").with_content(%r{access_log[ ]+/var/log/nginx/ssl-www\.rspec\.example\.com\.access\.log}) }
        it { should contain_concat__fragment("#{title}-ssl-header").with_content(%r{error_log[ ]+/var/log/nginx/ssl-www\.rspec\.example\.com\.error\.log}) }
        it { should contain_concat__fragment("#{title}-ssl-footer") }
        it { should contain_file("/etc/nginx/#{title}.crt") }
        it { should contain_file("/etc/nginx/#{title}.key") }
      end

      context 'when passenger_cgi_param is set' do
        let :params do default_params.merge({
          :passenger_cgi_param => { 'test1' => 'test value 1', 'test2' => 'test value 2', 'test3' => 'test value 3' }
        }) end

        it { should contain_concat__fragment("#{title}-header").with_content( /passenger_set_cgi_param  test1 test value 1;/ ) }
        it { should contain_concat__fragment("#{title}-header").with_content( /passenger_set_cgi_param  test2 test value 2;/ ) }
        it { should contain_concat__fragment("#{title}-header").with_content( /passenger_set_cgi_param  test3 test value 3;/ ) }
      end

      context 'when passenger_cgi_param is set and ssl => true' do
        let :params do default_params.merge({
          :passenger_cgi_param => { 'test1' => 'test value 1', 'test2' => 'test value 2', 'test3' => 'test value 3' },
          :ssl                 => true,
          :ssl_key             => 'dummy.key',
          :ssl_cert            => 'dummy.cert',
        }) end

        it { should contain_concat__fragment("#{title}-ssl-header").with_content( /passenger_set_cgi_param  test1 test value 1;/ ) }
        it { should contain_concat__fragment("#{title}-ssl-header").with_content( /passenger_set_cgi_param  test2 test value 2;/ ) }
        it { should contain_concat__fragment("#{title}-ssl-header").with_content( /passenger_set_cgi_param  test3 test value 3;/ ) }
      end

      context 'when vhost name is sanitized' do
        let :title do 'www rspec-vhost com' end
        let :params do default_params end

        it { should contain_concat('/etc/nginx/sites-available/www_rspec-vhost_com.conf') }
      end
    end
  end
end
