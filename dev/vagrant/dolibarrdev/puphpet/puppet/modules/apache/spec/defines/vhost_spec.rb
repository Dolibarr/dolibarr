require 'spec_helper'

describe 'apache::vhost', :type => :define do
  let :pre_condition do
    'class { "apache": default_vhost => false, }'
  end
  let :title do
    'rspec.example.com'
  end
  let :default_params do
    {
      :docroot => '/rspec/docroot',
      :port    => '84',
    }
  end
  describe 'os-dependent items' do
    context "on RedHat based systems" do
      let :default_facts do
        {
          :osfamily               => 'RedHat',
          :operatingsystemrelease => '6',
          :concat_basedir         => '/dne',
          :operatingsystem        => 'RedHat',
          :id                     => 'root',
          :kernel                 => 'Linux',
          :path                   => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
        }
      end
      let :params do default_params end
      let :facts do default_facts end
      it { should contain_class("apache") }
      it { should contain_class("apache::params") }
    end
    context "on Debian based systems" do
      let :default_facts do
        {
          :osfamily               => 'Debian',
          :operatingsystemrelease => '6',
          :concat_basedir         => '/dne',
          :lsbdistcodename        => 'squeeze',
          :operatingsystem        => 'Debian',
          :id                     => 'root',
          :kernel                 => 'Linux',
          :path                   => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
        }
      end
      let :params do default_params end
      let :facts do default_facts end
      it { should contain_class("apache") }
      it { should contain_class("apache::params") }
      it { should contain_file("25-rspec.example.com.conf").with(
        :ensure => 'present',
        :path   => '/etc/apache2/sites-available/25-rspec.example.com.conf'
      ) }
      it { should contain_file("25-rspec.example.com.conf symlink").with(
        :ensure => 'link',
        :path   => '/etc/apache2/sites-enabled/25-rspec.example.com.conf',
        :target => '/etc/apache2/sites-available/25-rspec.example.com.conf'
      ) }
    end
    context "on FreeBSD systems" do
      let :default_facts do
        {
          :osfamily               => 'FreeBSD',
          :operatingsystemrelease => '9',
          :concat_basedir         => '/dne',
          :operatingsystem        => 'FreeBSD',
          :id                     => 'root',
          :kernel                 => 'FreeBSD',
          :path                   => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
        }
      end
      let :params do default_params end
      let :facts do default_facts end
      it { should contain_class("apache") }
      it { should contain_class("apache::params") }
      it { should contain_file("25-rspec.example.com.conf").with(
        :ensure => 'present',
        :path   => '/usr/local/etc/apache22/Vhosts/25-rspec.example.com.conf'
      ) }
    end
  end
  describe 'os-independent items' do
    let :facts do
      {
        :osfamily               => 'Debian',
        :operatingsystemrelease => '6',
        :concat_basedir         => '/dne',
        :lsbdistcodename        => 'squeeze',
        :operatingsystem        => 'Debian',
        :id                     => 'root',
        :kernel                 => 'Linux',
        :path                   => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
      }
    end
    describe 'basic assumptions' do
      let :params do default_params end
      it { should contain_class("apache") }
      it { should contain_class("apache::params") }
      it { should contain_apache__listen(params[:port]) }
      it { should contain_apache__namevirtualhost("*:#{params[:port]}") }
    end

    # All match and notmatch should be a list of regexs and exact match strings
    context ".conf content" do
      [
        {
          :title => 'should contain docroot',
          :attr  => 'docroot',
          :value => '/not/default',
          :match => [/^  DocumentRoot "\/not\/default"$/,/  <Directory "\/not\/default">/],
        },
        {
          :title => 'should set a port',
          :attr  => 'port',
          :value => '8080',
          :match => [/^<VirtualHost \*:8080>$/],
        },
        {
          :title => 'should set an ip',
          :attr  => 'ip',
          :value => '10.0.0.1',
          :match => [/^<VirtualHost 10\.0\.0\.1:84>$/],
        },
        {
          :title => 'should set a serveradmin',
          :attr  => 'serveradmin',
          :value => 'test@test.com',
          :match => [/^  ServerAdmin test@test.com$/],
        },
        {
          :title => 'should enable ssl',
          :attr  => 'ssl',
          :value => true,
          :match => [/^  SSLEngine on$/],
        },
        {
          :title => 'should set a servername',
          :attr  => 'servername',
          :value => 'param.test',
          :match => [/^  ServerName param.test$/],
        },
        {
          :title => 'should accept server aliases',
          :attr  => 'serveraliases',
          :value => ['one.com','two.com'],
          :match => [
            /^  ServerAlias one\.com$/,
            /^  ServerAlias two\.com$/
          ],
        },
        {
          :title => 'should accept setenv',
          :attr  => 'setenv',
          :value => ['TEST1 one','TEST2 two'],
          :match => [
            /^  SetEnv TEST1 one$/,
            /^  SetEnv TEST2 two$/
          ],
        },
        {
          :title => 'should accept setenvif',
          :attr  => 'setenvif',
          ## These are bugged in rspec-puppet; the $1 is droped
          #:value => ['Host "^([^\.]*)\.website\.com$" CLIENT_NAME=$1'],
          #:match => ['  SetEnvIf Host "^([^\.]*)\.website\.com$" CLIENT_NAME=$1'],
          :value => ['Host "^test\.com$" VHOST_ACCESS=test'],
          :match => [/^  SetEnvIf Host "\^test\\.com\$" VHOST_ACCESS=test$/],
        },
        {
          :title => 'should accept options',
          :attr  => 'options',
          :value => ['Fake','Options'],
          :match => [/^    Options Fake Options$/],
        },
        {
          :title => 'should accept overrides',
          :attr  => 'override',
          :value => ['Fake', 'Override'],
          :match => [/^    AllowOverride Fake Override$/],
        },
        {
          :title => 'should accept logroot',
          :attr  => 'logroot',
          :value => '/fake/log',
          :match => [/CustomLog "\/fake\/log\//,/ErrorLog "\/fake\/log\//],
        },
        {
          :title => 'should accept log_level',
          :attr  => 'log_level',
          :value => 'info',
          :match => [/LogLevel info/],
        },
        {
          :title => 'should accept pipe destination for access log',
          :attr  => 'access_log_pipe',
          :value => '| /bin/fake/logging',
          :match => [/CustomLog "| \/bin\/fake\/logging" combined$/],
        },
        {
          :title => 'should accept pipe destination for error log',
          :attr  => 'error_log_pipe',
          :value => '| /bin/fake/logging',
          :match => [/ErrorLog "| \/bin\/fake\/logging" combined$/],
        },
        {
          :title => 'should accept syslog destination for access log',
          :attr  => 'access_log_syslog',
          :value => 'syslog:local1',
          :match => [/CustomLog "syslog:local1" combined$/],
        },
        {
          :title => 'should accept syslog destination for error log',
          :attr  => 'error_log_syslog',
          :value => 'syslog',
          :match => [/ErrorLog "syslog"$/],
        },
        {
          :title => 'should accept custom format for access logs',
          :attr  => 'access_log_format',
          :value => '%h %{X-Forwarded-For}i %l %u %t \"%r\" %s %b  \"%{Referer}i\" \"%{User-agent}i\" \"Host: %{Host}i\" %T %D',
          :match => [/CustomLog "\/var\/log\/.+_access\.log" "%h %\{X-Forwarded-For\}i %l %u %t \\"%r\\" %s %b  \\"%\{Referer\}i\\" \\"%\{User-agent\}i\\" \\"Host: %\{Host\}i\\" %T %D"$/],
        },
        {
          :title => 'should contain access logs',
          :attr  => 'access_log',
          :value => true,
          :match => [/CustomLog "\/var\/log\/.+_access\.log" combined$/],
        },
        {
          :title    => 'should not contain access logs',
          :attr     => 'access_log',
          :value    => false,
          :notmatch => [/CustomLog "\/var\/log\/.+_access\.log" combined$/],
        },
        {
          :title => 'should contain error logs',
          :attr  => 'error_log',
          :value => true,
          :match => [/ErrorLog.+$/],
        },
        {
          :title    => 'should not contain error logs',
          :attr     => 'error_log',
          :value    => false,
          :notmatch => [/ErrorLog.+$/],
        },
        {
          :title    => 'should set ErrorDocument 503',
          :attr     => 'error_documents',
          :value    => [ { 'error_code' => '503', 'document' => '"Go away, the backend is broken."'}],
          :match => [/^  ErrorDocument 503 "Go away, the backend is broken."$/],
        },
        {
          :title    => 'should set ErrorDocuments 503 407',
          :attr     => 'error_documents',
          :value    => [
            { 'error_code' => '503', 'document' => '/service-unavail'},
            { 'error_code' => '407', 'document' => 'https://example.com/proxy/login'},
          ],
          :match => [
            /^  ErrorDocument 503 \/service-unavail$/,
            /^  ErrorDocument 407 https:\/\/example\.com\/proxy\/login$/,
          ],
        },
        {
          :title    => 'should set ErrorDocument 503 in directory',
          :attr     => 'directories',
          :value    => { 'path' => '/srv/www', 'error_documents' => [{ 'error_code' => '503', 'document' => '"Go away, the backend is broken."'}] },
          :match => [/^    ErrorDocument 503 "Go away, the backend is broken."$/],
        },
        {
          :title    => 'should set ErrorDocuments 503 407 in directory',
          :attr     => 'directories',
          :value    => { 'path' => '/srv/www', 'error_documents' =>
          [
            { 'error_code' => '503', 'document' => '/service-unavail'},
            { 'error_code' => '407', 'document' => 'https://example.com/proxy/login'},
          ]},
          :match => [
            /^    ErrorDocument 503 \/service-unavail$/,
            /^    ErrorDocument 407 https:\/\/example\.com\/proxy\/login$/,
          ],
        },
        {
          :title => 'should accept a scriptalias',
          :attr  => 'scriptalias',
          :value => '/usr/scripts',
          :match => [
            /^  ScriptAlias \/cgi-bin "\/usr\/scripts"$/,
          ],
        },
        {
          :title    => 'should accept a single scriptaliases',
          :attr     => 'scriptaliases',
          :value    => { 'alias' => '/blah/', 'path' => '/usr/scripts' },
          :match    => [
            /^  ScriptAlias \/blah\/ "\/usr\/scripts"$/,
          ],
          :nomatch  => [/ScriptAlias \/cgi\-bin\//],
        },
        {
          :title    => 'should accept multiple scriptaliases',
          :attr     => 'scriptaliases',
          :value    => [ { 'alias' => '/blah', 'path' => '/usr/scripts' }, { 'alias' => '/blah2', 'path' => '/usr/scripts' } ],
          :match    => [
            /^  ScriptAlias \/blah "\/usr\/scripts"$/,
            /^  ScriptAlias \/blah2 "\/usr\/scripts"$/,
          ],
          :nomatch  => [/ScriptAlias \/cgi\-bin\//],
        },
        {
          :title    => 'should accept multiple scriptaliases with and without trailing slashes',
          :attr     => 'scriptaliases',
          :value    => [ { 'alias' => '/blah', 'path' => '/usr/scripts' }, { 'alias' => '/blah2/', 'path' => '/usr/scripts2/' } ],
          :match    => [
            /^  ScriptAlias \/blah "\/usr\/scripts"$/,
            /^  ScriptAlias \/blah2\/ "\/usr\/scripts2\/"$/,
          ],
          :nomatch  => [/ScriptAlias \/cgi\-bin\//],
        },
        {
          :title    => 'should accept a ScriptAliasMatch directive',
          :attr     => 'scriptaliases',
          ## XXX As mentioned above, rspec-puppet drops constructs like $1.
          ## Thus, these tests don't work as they should. As a workaround we
          ## use FOO instead of $1 here.
          :value    => [ { 'aliasmatch' => '^/cgi-bin(.*)', 'path' => '/usr/local/apache/cgi-binFOO' } ],
          :match    => [
            /^  ScriptAliasMatch \^\/cgi-bin\(\.\*\) "\/usr\/local\/apache\/cgi-binFOO"$/
          ],
        },
        {
          :title    => 'should accept multiple ScriptAliasMatch directives',
          :attr     => 'scriptaliases',
          ## XXX As mentioned above, rspec-puppet drops constructs like $1.
          ## Thus, these tests don't work as they should. As a workaround we
          ## use FOO instead of $1 here.
          :value    => [
            { 'aliasmatch' => '^/cgi-bin(.*)', 'path' => '/usr/local/apache/cgi-binFOO' },
            { 'aliasmatch' => '"(?x)^/git/(.*/(HEAD|info/refs|objects/(info/[^/]+|[0-9a-f]{2}/[0-9a-f]{38}|pack/pack-[0-9a-f]{40}\.(pack|idx))|git-(upload|receive)-pack))"', 'path' => '/var/www/bin/gitolite-suexec-wrapper/FOO' },
          ],
          :match    => [
            /^  ScriptAliasMatch \^\/cgi-bin\(\.\*\) "\/usr\/local\/apache\/cgi-binFOO"$/,
            /^  ScriptAliasMatch "\(\?x\)\^\/git\/\(\.\*\/\(HEAD\|info\/refs\|objects\/\(info\/\[\^\/\]\+\|\[0-9a-f\]\{2\}\/\[0-9a-f\]\{38\}\|pack\/pack-\[0-9a-f\]\{40\}\\\.\(pack\|idx\)\)\|git-\(upload\|receive\)-pack\)\)" "\/var\/www\/bin\/gitolite-suexec-wrapper\/FOO"$/,
          ],
        },
        {
          :title    => 'should accept mixed ScriptAlias and ScriptAliasMatch directives',
          :attr     => 'scriptaliases',
          ## XXX As mentioned above, rspec-puppet drops constructs like $1.
          ## Thus, these tests don't work as they should. As a workaround we
          ## use FOO instead of $1 here.
          :value    => [
            { 'aliasmatch' => '"(?x)^/git/(.*/(HEAD|info/refs|objects/(info/[^/]+|[0-9a-f]{2}/[0-9a-f]{38}|pack/pack-[0-9a-f]{40}\.(pack|idx))|git-(upload|receive)-pack))"', 'path' => '/var/www/bin/gitolite-suexec-wrapper/FOO' },
            { 'alias' => '/git', 'path' => '/var/www/gitweb/index.cgi' },
            { 'aliasmatch' => '^/cgi-bin(.*)', 'path' => '/usr/local/apache/cgi-binFOO' },
            { 'alias' => '/trac', 'path' => '/etc/apache2/trac.fcgi' },
          ],
          :match    => [
            /^  ScriptAliasMatch "\(\?x\)\^\/git\/\(\.\*\/\(HEAD\|info\/refs\|objects\/\(info\/\[\^\/\]\+\|\[0-9a-f\]\{2\}\/\[0-9a-f\]\{38\}\|pack\/pack-\[0-9a-f\]\{40\}\\\.\(pack\|idx\)\)\|git-\(upload\|receive\)-pack\)\)" "\/var\/www\/bin\/gitolite-suexec-wrapper\/FOO"$/,
            /^  ScriptAlias \/git "\/var\/www\/gitweb\/index\.cgi"$/,
            /^  ScriptAliasMatch \^\/cgi-bin\(\.\*\) "\/usr\/local\/apache\/cgi-binFOO"$/,
            /^  ScriptAlias \/trac "\/etc\/apache2\/trac.fcgi"$/,
          ],
        },
        {
          :title    => 'should accept proxy destinations',
          :attr     => 'proxy_dest',
          :value    => 'http://fake.com',
          :match    => [
            /^  ProxyPass          \/ http:\/\/fake.com\/$/,
            /^  <Location          \/>$/,
            /^    ProxyPassReverse http:\/\/fake.com\/$/,
            /^  <\/Location>$/,
          ],
          :notmatch => [/ProxyPass .+!$/],
        },
        {
          :title    => 'should accept proxy_pass hash',
          :attr     => 'proxy_pass',
          :value    => { 'path' => '/path-a', 'url' => 'http://fake.com/a' },
          :match    => [
            /^  ProxyPass \/path-a http:\/\/fake.com\/a$/,
            /^  <Location \/path-a>$/,
            /^    ProxyPassReverse http:\/\/fake.com\/a$/,
            /^  <\/Location>$/,

          ],
          :notmatch => [/ProxyPass .+!$/],
        },
        {
          :title    => 'should accept proxy_pass array of hash',
          :attr     => 'proxy_pass',
          :value    => [
            { 'path' => '/path-a/', 'url' => 'http://fake.com/a/' },
            { 'path' => '/path-b', 'url' => 'http://fake.com/b' },
          ],
          :match    => [
            /^  ProxyPass \/path-a\/ http:\/\/fake.com\/a\/$/,
            /^  <Location \/path-a\/>$/,
            /^    ProxyPassReverse http:\/\/fake.com\/a\/$/,
            /^  <\/Location>$/,
            /^  ProxyPass \/path-b http:\/\/fake.com\/b$/,
            /^  <Location \/path-b>$/,
            /^    ProxyPassReverse http:\/\/fake.com\/b$/,
            /^  <\/Location>$/,
          ],
          :notmatch => [/ProxyPass .+!$/],
        },
        {
          :title => 'should enable rack',
          :attr  => 'rack_base_uris',
          :value => ['/rack1','/rack2'],
          :match => [
            /^  RackBaseURI \/rack1$/,
            /^  RackBaseURI \/rack2$/,
          ],
        },
        {
          :title => 'should accept headers',
          :attr  => 'headers',
          :value => ['add something', 'merge something_else'],
          :match => [
            /^  Header add something$/,
            /^  Header merge something_else$/,
          ],
        },
        {
          :title => 'should accept request headers',
          :attr  => 'request_headers',
          :value => ['append something', 'unset something_else'],
          :match => [
            /^  RequestHeader append something$/,
            /^  RequestHeader unset something_else$/,
          ],
        },
        {
          :title => 'should accept rewrite rules',
          :attr  => 'rewrite_rule',
          :value => 'not a real rule',
          :match => [/^  RewriteRule not a real rule$/],
        },
        {
          :title => 'should accept rewrite rules',
          :attr  => 'rewrites',
          :value => [{'rewrite_rule' => ['not a real rule']}],
          :match => [/^  RewriteRule not a real rule$/],
        },
        {
          :title => 'should accept rewrite comment',
          :attr  => 'rewrites',
          :value => [{'comment' => 'rewrite comment', 'rewrite_rule' => ['not a real rule']}],
          :match => [/^  #rewrite comment/],
        },
        {
          :title => 'should accept rewrite conditions',
          :attr  => 'rewrites',
          :value => [{'comment' => 'redirect IE', 'rewrite_cond' => ['%{HTTP_USER_AGENT} ^MSIE'], 'rewrite_rule' => ['^index\.html$ welcome.html'],}],
          :match => [
            /^  #redirect IE$/,
            /^  RewriteCond %{HTTP_USER_AGENT} \^MSIE$/,
            /^  RewriteRule \^index\\\.html\$ welcome.html$/,
          ],
        },
        {
          :title => 'should accept multiple rewrites',
          :attr  => 'rewrites',
          :value => [
            {'rewrite_rule' => ['not a real rule']},
            {'rewrite_rule' => ['not a real rule two']},
          ],
          :match => [
            /^  RewriteRule not a real rule$/,
            /^  RewriteRule not a real rule two$/,
          ],
        },
        {
          :title => 'should block scm',
          :attr  => 'block',
          :value => 'scm',
          :match => [/^  <DirectoryMatch \.\*\\\.\(svn\|git\|bzr\)\/\.\*>$/],
        },
        {
          :title => 'should accept a custom fragment',
          :attr  => 'custom_fragment',
          :value => "  Some custom fragment line\n  That spans multiple lines",
          :match => [
            /^  Some custom fragment line$/,
            /^  That spans multiple lines$/,
            /^<\/VirtualHost>$/,
          ],
        },
        {
          :title => 'should accept an array of alias hashes',
          :attr  => 'aliases',
          :value => [ { 'alias' => '/', 'path' => '/var/www'} ],
          :match => [/^  Alias \/ "\/var\/www"$/],
        },
        {
          :title => 'should accept an alias hash',
          :attr  => 'aliases',
          :value => { 'alias' => '/', 'path' => '/var/www'},
          :match => [/^  Alias \/ "\/var\/www"$/],
        },
        {
          :title => 'should accept multiple aliases',
          :attr  => 'aliases',
          :value => [
            { 'alias' => '/', 'path' => '/var/www'},
            { 'alias' => '/cgi-bin', 'path' => '/var/www/cgi-bin'},
            { 'alias' => '/css', 'path' => '/opt/someapp/css'},
          ],
          :match => [
            /^  Alias \/ "\/var\/www"$/,
            /^  Alias \/cgi-bin "\/var\/www\/cgi-bin"$/,
            /^  Alias \/css "\/opt\/someapp\/css"$/,
          ],
        },
        {
          :title => 'should accept an aliasmatch hash',
          :attr  => 'aliases',
          ## XXX As mentioned above, rspec-puppet drops the $1. Thus, these
          # tests don't work.
          #:value => { 'aliasmatch' => '^/image/(.*).gif', 'path' => '/files/gifs/$1.gif' },
          #:match => [/^  AliasMatch \^\/image\/\(\.\*\)\.gif \/files\/gifs\/\$1\.gif$/],
        },
        {
          :title => 'should accept a array of alias and aliasmatch hashes mixed',
          :attr  => 'aliases',
          ## XXX As mentioned above, rspec-puppet drops the $1. Thus, these
          # tests don't work.
          #:value => [
          #  { 'alias' => '/css', 'path' => '/files/css' },
          #  { 'aliasmatch' => '^/image/(.*).gif', 'path' => '/files/gifs/$1.gif' },
          #  { 'aliasmatch' => '^/image/(.*).jpg', 'path' => '/files/jpgs/$1.jpg' },
          #  { 'alias' => '/image', 'path' => '/files/images' },
          #],
          #:match => [
          #  /^  Alias \/css \/files\/css$/,
          #  /^  AliasMatch \^\/image\/\(.\*\)\.gif \/files\/gifs\/\$1\.gif$/,
          #  /^  AliasMatch \^\/image\/\(.\*\)\.jpg \/files\/jpgs\/\$1\.jpg$/,
          #  /^  Alias \/image \/files\/images$/
          #],
        },
        {
          :title => 'should accept multiple additional includes',
          :attr  => 'additional_includes',
          :value => [
            '/tmp/proxy_group_a',
            '/tmp/proxy_group_b',
            '/tmp/proxy_group_c',
          ],
          :match => [
            /^  Include "\/tmp\/proxy_group_a"$/,
            /^  Include "\/tmp\/proxy_group_b"$/,
            /^  Include "\/tmp\/proxy_group_c"$/,
          ],
        },
        {
          :title => 'should accept a suPHP_Engine',
          :attr  => 'suphp_engine',
          :value => 'on',
          :match => [/^  suPHP_Engine on$/],
        },
        {
          :title => 'should accept a php_admin_flags',
          :attr  => 'php_admin_flags',
          :value => { 'engine' => 'on' },
          :match => [/^  php_admin_flag engine on$/],
        },
        {
          :title => 'should accept php_admin_values',
          :attr  => 'php_admin_values',
          :value => { 'open_basedir' => '/srv/web/www.com/:/usr/share/pear/' },
          :match => [/^  php_admin_value open_basedir \/srv\/web\/www.com\/:\/usr\/share\/pear\/$/],
        },
        {
          :title => 'should accept php_admin_flags in directories',
          :attr  => 'directories',
          :value => {
						'path'            => '/srv/www',
						'php_admin_flags' => { 'php_engine' => 'on' }
					},
          :match => [/^    php_admin_flag php_engine on$/],
        },
        {
          :title => 'should accept php_admin_values',
          :attr  => 'php_admin_values',
          :value => { 'open_basedir' => '/srv/web/www.com/:/usr/share/pear/' },
          :match => [/^  php_admin_value open_basedir \/srv\/web\/www.com\/:\/usr\/share\/pear\/$/],
        },
        {
          :title => 'should accept php_admin_values in directories',
          :attr  => 'directories',
          :value => {
            'path'             => '/srv/www',
            'php_admin_values' => { 'open_basedir' => '/srv/web/www.com/:/usr/share/pear/' }
          },
          :match => [/^    php_admin_value open_basedir \/srv\/web\/www.com\/:\/usr\/share\/pear\/$/],
        },
        {
          :title => 'should accept a wsgi script alias',
          :attr  => 'wsgi_script_aliases',
          :value => { '/' => '/var/www/myapp.wsgi'},
          :match => [/^  WSGIScriptAlias \/ "\/var\/www\/myapp.wsgi"$/],
        },
        {
          :title => 'should accept multiple wsgi aliases',
          :attr  => 'wsgi_script_aliases',
          :value => {
            '/wiki' => '/usr/local/wsgi/scripts/mywiki.wsgi',
            '/blog' => '/usr/local/wsgi/scripts/myblog.wsgi',
            '/'     => '/usr/local/wsgi/scripts/myapp.wsgi',
          },
          :match => [
            /^  WSGIScriptAlias \/wiki "\/usr\/local\/wsgi\/scripts\/mywiki.wsgi"$/,
            /^  WSGIScriptAlias \/blog "\/usr\/local\/wsgi\/scripts\/myblog.wsgi"$/,
            /^  WSGIScriptAlias \/ "\/usr\/local\/wsgi\/scripts\/myapp.wsgi"$/,
          ],
        },
        {
          :title => 'should accept a wsgi application group',
          :attr  => 'wsgi_application_group',
          :value => '%{GLOBAL}',
          :match => [/^  WSGIApplicationGroup %{GLOBAL}$/],
        },
        {
          :title => 'should set wsgi pass authorization',
          :attr  => 'wsgi_pass_authorization',
          :value => 'On',
          :match => [/^  WSGIPassAuthorization On$/],
        },
        {
          :title => 'should set wsgi pass authorization false',
          :attr  => 'wsgi_pass_authorization',
          :value => 'Off',
          :match => [/^  WSGIPassAuthorization Off$/],
        },
        {
          :title => 'should contain environment variables',
          :attr  => 'access_log_env_var',
          :value => 'admin',
          :match => [/CustomLog "\/var\/log\/.+_access\.log" combined env=admin$/]
        },
        {
          :title => 'should contain virtual_docroot',
          :attr  => 'virtual_docroot',
          :value => '/not/default',
          :match => [
            /^  VirtualDocumentRoot "\/not\/default"$/,
          ],
        },
        {
          :title    => 'should accept multiple directories',
          :attr     => 'directories',
          :value    => [
            { 'path' => '/opt/app' },
            { 'path' => '/var/www' },
            { 'path' => '/rspec/docroot'}
          ],
          :match    => [
            /^  <Directory "\/opt\/app">$/,
            /^  <Directory "\/var\/www">$/,
            /^  <Directory "\/rspec\/docroot">$/,
          ],
        },
      ].each do |param|
        describe "when #{param[:attr]} is #{param[:value]}" do
          let :params do default_params.merge({ param[:attr].to_sym => param[:value] }) end

          it { should contain_file("25-#{title}.conf").with_mode('0644') }
          if param[:match]
            it "#{param[:title]}: matches" do
              param[:match].each do |match|
                should contain_file("25-#{title}.conf").with_content( match )
              end
            end
          end
          if param[:notmatch]
            it "#{param[:title]}: notmatches" do
              param[:notmatch].each do |notmatch|
                should_not contain_file("25-#{title}.conf").with_content( notmatch )
              end
            end
          end
        end
      end
    end

    # Apache below 2.4 (Default Version). All match and notmatch should be a list of regexs and exact match strings
    context ".conf content with $apache_version < 2.4" do
      [
        {
          :title    => 'should accept a directory',
          :attr     => 'directories',
          :value    => { 'path' => '/opt/app' },
          :notmatch => ['  <Directory /rspec/docroot>'],
          :match    => [
            /^  <Directory "\/opt\/app">$/,
            /^    AllowOverride None$/,
            /^    Order allow,deny$/,
            /^    Allow from all$/,
            /^  <\/Directory>$/,
          ],
        },
        {
          :title    => 'should accept directory directives hash',
          :attr     => 'directories',
          :value    => {
            'path'              => '/opt/app',
            'headers'           => 'Set X-Robots-Tag "noindex, noarchive, nosnippet"',
            'allow'             => 'from rspec.org',
            'allow_override'    => 'Lol',
            'deny'              => 'from google.com',
            'options'           => '-MultiViews',
            'order'             => 'deny,yned',
            'passenger_enabled' => 'onf',
            'sethandler'        => 'None',
          },
          :match    => [
            /^  <Directory "\/opt\/app">$/,
            /^    Header Set X-Robots-Tag "noindex, noarchive, nosnippet"$/,
            /^    Allow from rspec.org$/,
            /^    AllowOverride Lol$/,
            /^    Deny from google.com$/,
            /^    Options -MultiViews$/,
            /^    Order deny,yned$/,
            /^    SetHandler None$/,
            /^    PassengerEnabled onf$/,
            /^  <\/Directory>$/,
          ],
        },
        {
          :title    => 'should accept directory directives with arrays and hashes',
          :attr     => 'directories',
          :value    => [
            {
              'path'              => '/opt/app1',
              'allow'             => 'from rspec.org',
              'allow_override'    => ['AuthConfig','Indexes'],
              'deny'              => 'from google.com',
              'options'           => ['-MultiViews','+MultiViews'],
              'order'             => ['deny','yned'],
              'passenger_enabled' => 'onf',
            },
            {
              'path'        => '/opt/app2',
              'addhandlers' => {
                'handler'    => 'cgi-script',
                'extensions' => '.cgi',
              },
            },
          ],
          :match    => [
            /^  <Directory "\/opt\/app1">$/,
            /^    Allow from rspec.org$/,
            /^    AllowOverride AuthConfig Indexes$/,
            /^    Deny from google.com$/,
            /^    Options -MultiViews \+MultiViews$/,
            /^    Order deny,yned$/,
            /^    PassengerEnabled onf$/,
            /^  <\/Directory>$/,
            /^  <Directory "\/opt\/app2">$/,
            /^    AllowOverride None$/,
            /^    Order allow,deny$/,
            /^    Allow from all$/,
            /^    AddHandler cgi-script .cgi$/,
            /^  <\/Directory>$/,
          ],
        },
        {
          :title => 'should accept location for provider',
          :attr  => 'directories',
          :value => {
            'path'     => '/',
            'provider' => 'location',
          },
          :notmatch => ['    AllowOverride None'],
          :match => [
            /^  <Location "\/">$/,
            /^    Order allow,deny$/,
            /^    Allow from all$/,
            /^  <\/Location>$/,
          ],
        },
        {
          :title => 'should accept files for provider',
          :attr  => 'directories',
          :value => {
            'path'     => 'index.html',
            'provider' => 'files',
          },
          :notmatch => ['    AllowOverride None'],
          :match => [
            /^  <Files "index.html">$/,
            /^    Order allow,deny$/,
            /^    Allow from all$/,
            /^  <\/Files>$/,
          ],
        },
        {
          :title => 'should accept files match for provider',
          :attr  => 'directories',
          :value => {
            'path'     => 'index.html',
            'provider' => 'filesmatch',
          },
          :notmatch => ['    AllowOverride None'],
          :match => [
            /^  <FilesMatch "index.html">$/,
            /^    Order allow,deny$/,
            /^    Allow from all$/,
            /^  <\/FilesMatch>$/,
          ],
        },
      ].each do |param|
        describe "when #{param[:attr]} is #{param[:value]}" do
          let :params do default_params.merge({
            param[:attr].to_sym => param[:value],
            :apache_version => '2.2',
          }) end

          it { should contain_file("25-#{title}.conf").with_mode('0644') }
          if param[:match]
            it "#{param[:title]}: matches" do
              param[:match].each do |match|
                should contain_file("25-#{title}.conf").with_content( match )
              end
            end
          end
          if param[:notmatch]
            it "#{param[:title]}: notmatches" do
              param[:notmatch].each do |notmatch|
                should_not contain_file("25-#{title}.conf").with_content( notmatch )
              end
            end
          end
        end
      end
    end

    # Apache equals or above 2.4. All match and notmatch should be a list of regexs and exact match strings
    context ".conf content with $apache_version >= 2.4" do
      [
        {
          :title    => 'should accept a directory',
          :attr     => 'directories',
          :value    => { 'path' => '/opt/app' },
          :notmatch => ['  <Directory /rspec/docroot>'],
          :match    => [
            /^  <Directory "\/opt\/app">$/,
            /^    AllowOverride None$/,
            /^    Require all granted$/,
            /^  <\/Directory>$/,
          ],
        },
        {
          :title    => 'should accept directory directives hash',
          :attr     => 'directories',
          :value    => {
            'path'              => '/opt/app',
            'headers'           => 'Set X-Robots-Tag "noindex, noarchive, nosnippet"',
            'allow_override'    => 'Lol',
            'options'           => '-MultiViews',
            'require'           => 'something denied',
            'passenger_enabled' => 'onf',
          },
          :match    => [
            /^  <Directory "\/opt\/app">$/,
            /^    Header Set X-Robots-Tag "noindex, noarchive, nosnippet"$/,
            /^    AllowOverride Lol$/,
            /^    Options -MultiViews$/,
            /^    Require something denied$/,
            /^    PassengerEnabled onf$/,
            /^  <\/Directory>$/,
          ],
        },
        {
          :title    => 'should accept directory directives with arrays and hashes',
          :attr     => 'directories',
          :value    => [
            {
              'path'              => '/opt/app1',
              'allow_override'    => ['AuthConfig','Indexes'],
              'options'           => ['-MultiViews','+MultiViews'],
              'require'           => ['host','example.org'],
              'passenger_enabled' => 'onf',
            },
            {
              'path'        => '/opt/app2',
              'addhandlers' => {
                'handler'    => 'cgi-script',
                'extensions' => '.cgi',
              },
            },
          ],
          :match    => [
            /^  <Directory "\/opt\/app1">$/,
            /^    AllowOverride AuthConfig Indexes$/,
            /^    Options -MultiViews \+MultiViews$/,
            /^    Require host example.org$/,
            /^    PassengerEnabled onf$/,
            /^  <\/Directory>$/,
            /^  <Directory "\/opt\/app2">$/,
            /^    AllowOverride None$/,
            /^    Require all granted$/,
            /^    AddHandler cgi-script .cgi$/,
            /^  <\/Directory>$/,
          ],
        },
        {
          :title => 'should accept location for provider',
          :attr  => 'directories',
          :value => {
            'path'     => '/',
            'provider' => 'location',
          },
          :notmatch => ['    AllowOverride None'],
          :match => [
            /^  <Location "\/">$/,
            /^    Require all granted$/,
            /^  <\/Location>$/,
          ],
        },
        {
          :title => 'should accept files for provider',
          :attr  => 'directories',
          :value => {
            'path'     => 'index.html',
            'provider' => 'files',
          },
          :notmatch => ['    AllowOverride None'],
          :match => [
            /^  <Files "index.html">$/,
            /^    Require all granted$/,
            /^  <\/Files>$/,
          ],
        },
        {
          :title => 'should accept files match for provider',
          :attr  => 'directories',
          :value => {
            'path'     => 'index.html',
            'provider' => 'filesmatch',
          },
          :notmatch => ['    AllowOverride None'],
          :match => [
            /^  <FilesMatch "index.html">$/,
            /^    Require all granted$/,
            /^  <\/FilesMatch>$/,
          ],
        },
      ].each do |param|
        describe "when #{param[:attr]} is #{param[:value]}" do
          let :params do default_params.merge({
            param[:attr].to_sym => param[:value],
            :apache_version => '2.4',
          }) end

          it { should contain_file("25-#{title}.conf").with_mode('0644') }
          if param[:match]
            it "#{param[:title]}: matches" do
              param[:match].each do |match|
                should contain_file("25-#{title}.conf").with_content( match )
              end
            end
          end
          if param[:notmatch]
            it "#{param[:title]}: notmatches" do
              param[:notmatch].each do |notmatch|
                should_not contain_file("25-#{title}.conf").with_content( notmatch )
              end
            end
          end
        end
      end
    end

    # All match and notmatch should be a list of regexs and exact match strings
    context ".conf content with SSL" do
      [
        {
            :title => 'should accept setting SSLCertificateFile',
            :attr  => 'ssl_cert',
            :value => '/path/to/cert.pem',
            :match => [/^  SSLCertificateFile      "\/path\/to\/cert\.pem"$/],
        },
        {
            :title => 'should accept setting SSLCertificateKeyFile',
            :attr  => 'ssl_key',
            :value => '/path/to/cert.pem',
            :match => [/^  SSLCertificateKeyFile   "\/path\/to\/cert\.pem"$/],
        },
        {
            :title => 'should accept setting SSLCertificateChainFile',
            :attr  => 'ssl_chain',
            :value => '/path/to/cert.pem',
            :match => [/^  SSLCertificateChainFile "\/path\/to\/cert\.pem"$/],
        },
        {
            :title => 'should accept setting SSLCertificatePath',
            :attr  => 'ssl_certs_dir',
            :value => '/path/to/certs',
            :match => [/^  SSLCACertificatePath    "\/path\/to\/certs"$/],
        },
        {
            :title => 'should accept setting SSLCertificateFile',
            :attr  => 'ssl_ca',
            :value => '/path/to/ca.pem',
            :match => [/^  SSLCACertificateFile    "\/path\/to\/ca\.pem"$/],
        },
        {
            :title => 'should accept setting SSLRevocationPath',
            :attr  => 'ssl_crl_path',
            :value => '/path/to/crl',
            :match => [/^  SSLCARevocationPath     "\/path\/to\/crl"$/],
        },
        {
            :title => 'should accept setting SSLRevocationFile',
            :attr  => 'ssl_crl',
            :value => '/path/to/crl.pem',
            :match => [/^  SSLCARevocationFile     "\/path\/to\/crl\.pem"$/],
        },
        {
            :title => 'should accept setting SSLProxyEngine',
            :attr  => 'ssl_proxyengine',
            :value => true,
            :match => [/^  SSLProxyEngine On$/],
        },
        {
            :title => 'should accept setting SSLProtocol',
            :attr  => 'ssl_protocol',
            :value => 'all -SSLv2',
            :match => [/^  SSLProtocol             all -SSLv2$/],
        },
        {
            :title => 'should accept setting SSLCipherSuite',
            :attr  => 'ssl_cipher',
            :value => 'RC4-SHA:HIGH:!ADH:!SSLv2',
            :match => [/^  SSLCipherSuite          RC4-SHA:HIGH:!ADH:!SSLv2$/],
        },
        {
            :title => 'should accept setting SSLHonorCipherOrder',
            :attr  => 'ssl_honorcipherorder',
            :value => 'On',
            :match => [/^  SSLHonorCipherOrder     On$/],
        },
        {
            :title => 'should accept setting SSLVerifyClient',
            :attr  => 'ssl_verify_client',
            :value => 'optional',
            :match => [/^  SSLVerifyClient         optional$/],
        },
        {
            :title => 'should accept setting SSLVerifyDepth',
            :attr  => 'ssl_verify_depth',
            :value => '1',
            :match => [/^  SSLVerifyDepth          1$/],
        },
        {
            :title => 'should accept setting SSLOptions with a string',
            :attr  => 'ssl_options',
            :value => '+ExportCertData',
            :match => [/^  SSLOptions \+ExportCertData$/],
        },
        {
            :title => 'should accept setting SSLOptions with an array',
            :attr  => 'ssl_options',
            :value => ['+StrictRequire','+ExportCertData'],
            :match => [/^  SSLOptions \+StrictRequire \+ExportCertData/],
        },
        {
            :title => 'should accept setting SSLOptions with a string in directories',
            :attr  => 'directories',
            :value => { 'path' => '/srv/www', 'ssl_options' => '+ExportCertData'},
            :match => [/^    SSLOptions \+ExportCertData$/],
        },
        {
            :title => 'should accept setting SSLOptions with an array in directories',
            :attr  => 'directories',
            :value => { 'path' => '/srv/www', 'ssl_options' => ['-StdEnvVars','+ExportCertData']},
            :match => [/^    SSLOptions -StdEnvVars \+ExportCertData/],
        },
      ].each do |param|
        describe "when #{param[:attr]} is #{param[:value]} with SSL" do
          let :params do
            default_params.merge( {
              param[:attr].to_sym => param[:value],
              :ssl                => true,
            } )
          end
          it { should contain_file("25-#{title}.conf").with_mode('0644') }
          if param[:match]
            it "#{param[:title]}: matches" do
              param[:match].each do |match|
                should contain_file("25-#{title}.conf").with_content( match )
              end
            end
          end
          if param[:notmatch]
            it "#{param[:title]}: notmatches" do
              param[:notmatch].each do |notmatch|
                should_not contain_file("25-#{title}.conf").with_content( notmatch )
              end
            end
          end
        end
      end
    end

    context 'attribute resources' do
      describe 'when access_log_file and access_log_pipe are specified' do
        let :params do default_params.merge({
          :access_log_file => 'fake.log',
          :access_log_pipe => '| /bin/fake',
        }) end
        it 'should cause a failure' do
          expect { subject }.to raise_error(Puppet::Error, /'access_log_file' and 'access_log_pipe' cannot be defined at the same time/)
        end
      end
      describe 'when error_log_file and error_log_pipe are specified' do
        let :params do default_params.merge({
          :error_log_file => 'fake.log',
          :error_log_pipe => '| /bin/fake',
        }) end
        it 'should cause a failure' do
          expect { subject }.to raise_error(Puppet::Error, /'error_log_file' and 'error_log_pipe' cannot be defined at the same time/)
        end
      end
      describe 'when docroot owner and mode is specified' do
        let :params do default_params.merge({
          :docroot_owner => 'testuser',
          :docroot_group => 'testgroup',
          :docroot_mode  => '0750',
        }) end
        it 'should set vhost ownership and permissions' do
          should contain_file(params[:docroot]).with({
            :ensure => :directory,
            :owner  => 'testuser',
            :group  => 'testgroup',
            :mode   => '0750',
          })
        end
      end

      describe 'when wsgi_daemon_process and wsgi_daemon_process_options are specified' do
        let :params do default_params.merge({
          :wsgi_daemon_process         => 'example.org',
          :wsgi_daemon_process_options => { 'processes' => '2', 'threads' => '15' },
        }) end
        it 'should set wsgi_daemon_process_options' do
          should contain_file("25-#{title}.conf").with_content(
            /^  WSGIDaemonProcess example.org processes=2 threads=15$/
          )
        end
      end

      describe 'when wsgi_import_script and wsgi_import_script_options are specified' do
        let :params do default_params.merge({
          :wsgi_import_script         => '/var/www/demo.wsgi',
          :wsgi_import_script_options => { 'application-group' => '%{GLOBAL}', 'process-group' => 'wsgi' },
        }) end
        it 'should set wsgi_import_script_options' do
          should contain_file("25-#{title}.conf").with_content(
            /^  WSGIImportScript \/var\/www\/demo.wsgi application-group=%{GLOBAL} process-group=wsgi$/
          )
        end
      end

      describe 'when rewrites are specified' do
        let :params do default_params.merge({
          :rewrites => [
            {
              'comment'      => 'test rewrites',
              'rewrite_base' => '/mytestpath/',
              'rewrite_cond' => ['%{HTTP_USER_AGENT} ^Lynx/ [OR]', '%{HTTP_USER_AGENT} ^Mozilla/[12]'],
              'rewrite_rule' => ['^index\.html$ welcome.html', '^index\.cgi$ index.php'],
            }
          ]
        }) end
        it 'should set RewriteConds and RewriteRules' do
          should contain_file("25-#{title}.conf").with_content(
            /^  #test rewrites$/
          )
          should contain_file("25-#{title}.conf").with_content(
            /^  RewriteCond %\{HTTP_USER_AGENT\} \^Lynx\/ \[OR\]$/
          )
          should contain_file("25-#{title}.conf").with_content(
            /^  RewriteBase \/mytestpath\/$/
          )
          should contain_file("25-#{title}.conf").with_content(
            /^  RewriteCond %\{HTTP_USER_AGENT\} \^Mozilla\/\[12\]$/
          )
          should contain_file("25-#{title}.conf").with_content(
            /^  RewriteRule \^index\\.html\$ welcome.html$/
          )
          should contain_file("25-#{title}.conf").with_content(
            /^  RewriteRule \^index\\.cgi\$ index.php$/
          )
        end
      end

      describe 'when rewrite_rule and rewrite_cond are specified' do
        let :params do default_params.merge({
          :rewrite_cond => '%{HTTPS} off',
          :rewrite_rule => '(.*) https://%{HTTPS_HOST}%{REQUEST_URI}',
        }) end
        it 'should set RewriteCond' do
          should contain_file("25-#{title}.conf").with_content(
            /^  RewriteCond %\{HTTPS\} off$/
          )
        end
      end

      describe 'when action is specified specified' do
        let :params do default_params.merge({
          :action => 'php-fastcgi',
        }) end
        it 'should set Action' do
          should contain_file("25-#{title}.conf").with_content(
            /^  Action php-fastcgi \/cgi-bin virtual$/
          )
        end
      end

      describe 'when suphp_engine is on and suphp_configpath is specified' do
        let :params do default_params.merge({
          :suphp_engine     => 'on',
          :suphp_configpath => '/etc/php5/apache2',
        }) end
        it 'should set suphp_configpath' do
          should contain_file("25-#{title}.conf").with_content(
            /^  suPHP_ConfigPath "\/etc\/php5\/apache2"$/
          )
        end
      end

      describe 'when suphp_engine is on and suphp_addhandler is specified' do
        let :params do default_params.merge({
          :suphp_engine     => 'on',
          :suphp_addhandler => 'x-httpd-php',
        }) end
        it 'should set suphp_addhandler' do
          should contain_file("25-#{title}.conf").with_content(
            /^  suPHP_AddHandler x-httpd-php/
          )
        end
      end

      describe 'when suphp_engine is on and suphp { user & group } is specified' do
        let :params do default_params.merge({
          :suphp_engine     => 'on',
          :directories      => { 'path' => '/srv/www',
            'suphp' => { 'user' => 'myappuser', 'group' => 'myappgroup' },
          }
        }) end
        it 'should set suphp_UserGroup' do
          should contain_file("25-#{title}.conf").with_content(
            /^    suPHP_UserGroup myappuser myappgroup/
          )
        end
      end

      describe 'priority/default settings' do
        describe 'when neither priority/default is specified' do
          let :params do default_params end
          it { should contain_file("25-#{title}.conf").with_path(
            /25-#{title}.conf/
          ) }
        end
        describe 'when both priority/default_vhost is specified' do
          let :params do
            default_params.merge({
              :priority      => 15,
              :default_vhost => true,
            })
          end
          it { should contain_file("15-#{title}.conf").with_path(
            /15-#{title}.conf/
          ) }
        end
        describe 'when only priority is specified' do
          let :params do
            default_params.merge({ :priority => 14, })
          end
          it { should contain_file("14-#{title}.conf").with_path(
            /14-#{title}.conf/
          ) }
        end
        describe 'when only default is specified' do
          let :params do
            default_params.merge({ :default_vhost => true, })
          end
          it { should contain_file("10-#{title}.conf").with_path(
            /10-#{title}.conf/
          ) }
        end
      end

      describe 'fcgid directory options' do
        describe 'No fcgiwrapper' do
          let :params do
            default_params.merge({
              :directories      => { 'path' => '/srv/www' },
            })
          end

          it { should_not contain_file("25-#{title}.conf").with_content(%r{FcgidWrapper}) }
        end

        describe 'Only a command' do
          let :params do
            default_params.merge({
              :directories      => { 'path' => '/srv/www',
                'fcgiwrapper' => { 'command' => '/usr/local/bin/fcgiwrapper' },
              }
            })
          end

          it { should contain_file("25-#{title}.conf").with_content(%r{^    FcgidWrapper /usr/local/bin/fcgiwrapper  $}) }
        end

        describe 'All parameters' do
          let :params do
            default_params.merge({
              :directories    => { 'path' => '/srv/www',
                'fcgiwrapper' => { 'command' => '/usr/local/bin/fcgiwrapper', 'suffix' => '.php', 'virtual' => 'virtual' },
              }
            })
          end

          it { should contain_file("25-#{title}.conf").with_content(%r{^    FcgidWrapper /usr/local/bin/fcgiwrapper .php virtual$}) }
        end
      end

      describe 'various ip/port combos' do
        describe 'when ip_based is true' do
          let :params do default_params.merge({ :ip_based => true }) end
          it 'should not specify a NameVirtualHost' do
            should contain_apache__listen(params[:port])
            should_not contain_apache__namevirtualhost("*:#{params[:port]}")
          end
        end

        describe 'when ip_based is default' do
          let :params do default_params end
          it 'should specify a NameVirtualHost' do
            should contain_apache__listen(params[:port])
            should contain_apache__namevirtualhost("*:#{params[:port]}")
          end
        end

        describe 'when an ip is set' do
          let :params do default_params.merge({ :ip => '10.0.0.1' }) end
          it 'should specify a NameVirtualHost for the ip' do
            should_not contain_apache__listen(params[:port])
            should contain_apache__listen("10.0.0.1:#{params[:port]}")
            should contain_apache__namevirtualhost("10.0.0.1:#{params[:port]}")
          end
        end

        describe 'an ip_based vhost without a port' do
          let :params do
            {
              :docroot  => '/fake',
              :ip       => '10.0.0.1',
              :ip_based => true,
            }
          end
          it 'should specify a NameVirtualHost for the ip' do
            should_not contain_apache__listen(params[:ip])
            should_not contain_apache__namevirtualhost(params[:ip])
            should contain_file("25-#{title}.conf").with_content %r{<VirtualHost 10\.0\.0\.1>}
          end
        end
      end

      describe 'when suexec_user_group is specified' do
        let :params do
          default_params.merge({
            :suexec_user_group => 'nobody nogroup',
          })
        end

        it { should contain_file("25-#{title}.conf").with_content %r{^  SuexecUserGroup nobody nogroup$} }
      end

      describe 'redirect rules' do
        describe 'without lockstep arrays' do
          let :params do
            default_params.merge({
              :redirect_source => [
                '/login',
                '/logout',
              ],
              :redirect_dest   => [
                'http://10.0.0.10/login',
                'http://10.0.0.10/logout',
              ],
              :redirect_status   => [
                'permanent',
                '',
              ],
            })
          end

          it { should contain_file("25-#{title}.conf").with_content %r{  Redirect permanent /login http://10\.0\.0\.10/login} }
          it { should contain_file("25-#{title}.conf").with_content %r{  Redirect  /logout http://10\.0\.0\.10/logout} }
        end
        describe 'redirect match rules' do
          let :params do
            default_params.merge({
              :redirectmatch_status => [
                '404',
              ],
              :redirectmatch_regexp   => [
                '/\.git(/.*|$)',
              ],
            })
          end

          it { should contain_file("25-#{title}.conf").with_content %r{  RedirectMatch 404 } }
        end
        describe 'without a status' do
          let :params do
            default_params.merge({
              :redirect_source => [
                '/login',
                '/logout',
              ],
              :redirect_dest   => [
                'http://10.0.0.10/login',
                'http://10.0.0.10/logout',
              ],
            })
          end

          it { should contain_file("25-#{title}.conf").with_content %r{  Redirect  /login http://10\.0\.0\.10/login} }
          it { should contain_file("25-#{title}.conf").with_content %r{  Redirect  /logout http://10\.0\.0\.10/logout} }
        end
        describe 'with a single status and dest' do
          let :params do
            default_params.merge({
              :redirect_source => [
                '/login',
                '/logout',
              ],
              :redirect_dest   => 'http://10.0.0.10/test',
              :redirect_status => 'permanent',
            })
          end

          it { should contain_file("25-#{title}.conf").with_content %r{  Redirect permanent /login http://10\.0\.0\.10/test} }
          it { should contain_file("25-#{title}.conf").with_content %r{  Redirect permanent /logout http://10\.0\.0\.10/test} }
        end

        describe 'with a directoryindex specified' do
          let :params do
            default_params.merge({
              :directoryindex => 'index.php'
            })
          end
          it { should contain_file("25-#{title}.conf").with_content %r{DirectoryIndex index.php} }
	end
      end
    end
  end
end
