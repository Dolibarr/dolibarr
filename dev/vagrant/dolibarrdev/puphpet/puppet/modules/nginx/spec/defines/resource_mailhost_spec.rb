require 'spec_helper'

describe 'nginx::resource::mailhost' do
  let :title do
    'www.rspec.example.com'
  end
  let :facts do
    {
      :osfamily        => 'debian',
      :operatingsystem => 'debian',
      :ipaddress6      => '::',
    }
  end
  let :default_params do
    {
      :listen_port => 25,
      :ipv6_enable => true,
    }
  end
  let :pre_condition do
    [
      'include ::nginx::config',
    ]
  end

  describe 'os-independent items' do

    describe 'basic assumptions' do
      let :params do default_params end
      it { should contain_class("nginx::config") }
      it { should contain_concat("/etc/nginx/conf.mail.d/#{title}.conf").with({
        'owner' => 'root',
        'group' => 'root',
        'mode'  => '0644',
      })}
      it { should contain_concat__fragment("#{title}-header") }
      it { should_not contain_concat__fragment("#{title}-ssl") }
    end

    describe "mailhost template content" do
      [
        {
          :title => 'should set the IPv4 listen IP',
          :attr  => 'listen_ip',
          :value => '127.0.0.1',
          :match => '  listen                127.0.0.1:25;',
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
          :match => '  listen                *:25 spdy default;',
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
          :title => 'should set protocol',
          :attr  => 'protocol',
          :value => 'test-protocol',
          :match => '  protocol              test-protocol;',
        },
        {
          :title => 'should set xclient',
          :attr  => 'xclient',
          :value => 'test-xclient',
          :match => '  xclient               test-xclient;',
        },
        {
          :title => 'should set auth_http',
          :attr  => 'auth_http',
          :value => 'test-auth_http',
          :match => '  auth_http             test-auth_http;',
        },
        {
          :title => 'should set starttls',
          :attr  => 'starttls',
          :value => 'on',
          :match => '  starttls              on;',
        },
        {
          :title => 'should set starttls',
          :attr  => 'starttls',
          :value => 'only',
          :match => '  starttls              only;',
        },
        {
          :title    => 'should not enable SSL',
          :attr     => 'starttls',
          :value    => 'off',
          :notmatch => /  ssl_session_timeout  5m;/,
        },
      ].each do |param|
        context "when #{param[:attr]} is #{param[:value]}" do
          let :default_params do {
            :listen_port => 25,
            :ipv6_enable => true,
            :ssl_cert    => 'dummy.crt',
            :ssl_key     => 'dummy.key',
          } end
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

    describe "mailhost template content (SSL enabled)" do
      [
        {
          :title => 'should enable SSL',
          :attr  => 'starttls',
          :value => 'on',
          :match => '  ssl_session_timeout  5m;',
        },
        {
          :title => 'should enable SSL',
          :attr  => 'starttls',
          :value => 'only',
          :match => '  ssl_session_timeout  5m;',
        },
        {
          :title    => 'should not enable SSL',
          :attr     => 'starttls',
          :value    => 'off',
          :notmatch => /  ssl_session_timeout  5m;/,
        },
        {
          :title => 'should set ssl_certificate',
          :attr  => 'ssl_cert',
          :value => 'test-ssl-cert',
          :match => '  ssl_certificate      test-ssl-cert;',
        },
        {
          :title => 'should set ssl_certificate_key',
          :attr  => 'ssl_key',
          :value => 'test-ssl-cert-key',
          :match => '  ssl_certificate_key  test-ssl-cert-key;',
        },
      ].each do |param|
        context "when #{param[:attr]} is #{param[:value]}" do
          let :default_params do {
            :listen_port => 25,
            :starttls    => 'on',
            :ssl_cert    => 'dummy.crt',
            :ssl_key     => 'dummy.key',
          } end
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

    describe "mailhost_ssl template content" do
      [
        {
          :title => 'should set the IPv4 SSL listen port',
          :attr  => 'ssl_port',
          :value => '45',
          :match => '  listen       45;',
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
          :title => 'should set protocol',
          :attr  => 'protocol',
          :value => 'test-protocol',
          :match => '  protocol              test-protocol;',
        },
        {
          :title => 'should set xclient',
          :attr  => 'xclient',
          :value => 'test-xclient',
          :match => '  xclient               test-xclient;',
        },
        {
          :title => 'should set auth_http',
          :attr  => 'auth_http',
          :value => 'test-auth_http',
          :match => '  auth_http             test-auth_http;',
        },
        {
          :title => 'should set ssl_certificate',
          :attr  => 'ssl_cert',
          :value => 'test-ssl-cert',
          :match => '  ssl_certificate      test-ssl-cert;',
        },
        {
          :title => 'should set ssl_certificate_key',
          :attr  => 'ssl_key',
          :value => 'test-ssl-cert-key',
          :match => '  ssl_certificate_key  test-ssl-cert-key;',
        },
      ].each do |param|
        context "when #{param[:attr]} is #{param[:value]}" do
          let :default_params do {
            :listen_port => 25,
            :ipv6_enable => true,
            :ssl         => true,
            :ssl_cert    => 'dummy.crt',
            :ssl_key     => 'dummy.key',
          } end
          let :params do default_params.merge({ param[:attr].to_sym => param[:value] }) end

          it { should contain_concat__fragment("#{title}-ssl") }
          it param[:title] do
            lines = subject.resource('concat::fragment', "#{title}-ssl").send(:parameters)[:content].split("\n")
            (lines & Array(param[:match])).should == Array(param[:match])
            Array(param[:notmatch]).each do |item|
              should contain_concat__fragment("#{title}-ssl").without_content(item)
            end
          end
        end
      end
    end

    context 'attribute resources' do
      context "SSL cert missing and ssl => true" do
        let :params do default_params.merge({
          :ssl     => true,
          :ssl_key => 'key',
        }) end

        it { expect { should contain_class('nginx::resource::vhost') }.to raise_error(Puppet::Error, %r{nginx: SSL certificate/key \(ssl_cert/ssl_cert\) and/or SSL Private must be defined and exist on the target system\(s\)}) }
      end

      context "SSL key missing and ssl => true" do
        let :params do default_params.merge({
          :ssl      => true,
          :ssl_cert => 'cert',
        }) end

        it { expect { should contain_class('nginx::resource::vhost') }.to raise_error(Puppet::Error, %r{nginx: SSL certificate/key \(ssl_cert/ssl_cert\) and/or SSL Private must be defined and exist on the target system\(s\)}) }
      end

      context "SSL cert missing and starttls => 'on'" do
        let :params do default_params.merge({
          :starttls => 'on',
          :ssl_key  => 'key',
        }) end

        it { expect { should contain_class('nginx::resource::vhost') }.to raise_error(Puppet::Error, %r{nginx: SSL certificate/key \(ssl_cert/ssl_cert\) and/or SSL Private must be defined and exist on the target system\(s\)}) }
      end

      context "SSL key missing and starttls => 'on'" do
        let :params do default_params.merge({
          :starttls => 'on',
          :ssl_cert => 'cert',
        }) end

        it { expect { should contain_class('nginx::resource::vhost') }.to raise_error(Puppet::Error, %r{nginx: SSL certificate/key \(ssl_cert/ssl_cert\) and/or SSL Private must be defined and exist on the target system\(s\)}) }
      end

      context "SSL cert missing and starttls => 'only'" do
        let :params do default_params.merge({
          :starttls => 'only',
          :ssl_key  => 'key',
        }) end

        it { expect { should contain_class('nginx::resource::vhost') }.to raise_error(Puppet::Error, %r{nginx: SSL certificate/key \(ssl_cert/ssl_cert\) and/or SSL Private must be defined and exist on the target system\(s\)}) }
      end

      context "SSL key missing and starttls => 'only'" do
        let :params do default_params.merge({
          :starttls => 'only',
          :ssl_cert => 'cert',
        }) end

        it { expect { should contain_class('nginx::resource::vhost') }.to raise_error(Puppet::Error, %r{nginx: SSL certificate/key \(ssl_cert/ssl_cert\) and/or SSL Private must be defined and exist on the target system\(s\)}) }
      end

      context 'when listen_port != ssl_port' do
        let :params do default_params.merge({
          :listen_port => 80,
          :ssl_port    => 443,
        }) end

        it { should contain_concat__fragment("#{title}-header") }
      end

      context 'when listen_port == ssl_port' do
        let :params do default_params.merge({
          :listen_port => 80,
          :ssl_port    => 80,
        }) end

        it { should_not contain_concat__fragment("#{title}-header") }
      end

      context 'when ssl => true' do
        let :params do default_params.merge({
          :ensure   => 'absent',
          :ssl      => true,
          :ssl_key  => 'dummy.key',
          :ssl_cert => 'dummy.cert',
        }) end

        it { should contain_concat__fragment("#{title}-header") }
        it { should contain_concat__fragment("#{title}-ssl") }
      end

      context 'when ssl => false' do
        let :params do default_params.merge({
          :ensure => 'absent',
          :ssl    => false,
        }) end

        it { should contain_concat__fragment("#{title}-header") }
        it { should_not contain_concat__fragment("#{title}-ssl") }
      end
    end
  end
end
