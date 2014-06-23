require 'spec_helper_acceptance'

case fact('osfamily')
when 'RedHat'
  vhostd = '/etc/httpd/conf.d'
when 'Debian'
  vhostd = '/etc/apache2/sites-available'
end

describe 'apache ssl', :unless => UNSUPPORTED_PLATFORMS.include?(fact('osfamily')) do

  describe 'ssl parameters' do
    it 'runs without error' do
      pp = <<-EOS
        class { 'apache':
          service_ensure       => stopped,
          default_ssl_vhost    => true,
          default_ssl_cert     => '/tmp/ssl_cert',
          default_ssl_key      => '/tmp/ssl_key',
          default_ssl_chain    => '/tmp/ssl_chain',
          default_ssl_ca       => '/tmp/ssl_ca',
          default_ssl_crl_path => '/tmp/ssl_crl_path',
          default_ssl_crl      => '/tmp/ssl_crl',
        }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    describe file("#{vhostd}/15-default-ssl.conf") do
      it { should be_file }
      it { should contain 'SSLCertificateFile      "/tmp/ssl_cert"' }
      it { should contain 'SSLCertificateKeyFile   "/tmp/ssl_key"' }
      it { should contain 'SSLCertificateChainFile "/tmp/ssl_chain"' }
      it { should contain 'SSLCACertificateFile    "/tmp/ssl_ca"' }
      it { should contain 'SSLCARevocationPath     "/tmp/ssl_crl_path"' }
      it { should contain 'SSLCARevocationFile     "/tmp/ssl_crl"' }
    end
  end

  describe 'vhost ssl parameters' do
    it 'runs without error' do
      pp = <<-EOS
        class { 'apache':
          service_ensure       => stopped,
        }

        apache::vhost { 'test_ssl':
          docroot              => '/tmp/test',
          ssl                  => true,
          ssl_cert             => '/tmp/ssl_cert',
          ssl_key              => '/tmp/ssl_key',
          ssl_chain            => '/tmp/ssl_chain',
          ssl_ca               => '/tmp/ssl_ca',
          ssl_crl_path         => '/tmp/ssl_crl_path',
          ssl_crl              => '/tmp/ssl_crl',
          ssl_certs_dir        => '/tmp',
          ssl_protocol         => 'test',
          ssl_cipher           => 'test',
          ssl_honorcipherorder => 'test',
          ssl_verify_client    => 'test',
          ssl_verify_depth     => 'test',
          ssl_options          => ['test', 'test1'],
          ssl_proxyengine      => true,
        }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    describe file("#{vhostd}/25-test_ssl.conf") do
      it { should be_file }
      it { should contain 'SSLCertificateFile      "/tmp/ssl_cert"' }
      it { should contain 'SSLCertificateKeyFile   "/tmp/ssl_key"' }
      it { should contain 'SSLCertificateChainFile "/tmp/ssl_chain"' }
      it { should contain 'SSLCACertificateFile    "/tmp/ssl_ca"' }
      it { should contain 'SSLCARevocationPath     "/tmp/ssl_crl_path"' }
      it { should contain 'SSLCARevocationFile     "/tmp/ssl_crl"' }
      it { should contain 'SSLProxyEngine On' }
      it { should contain 'SSLProtocol             test' }
      it { should contain 'SSLCipherSuite          test' }
      it { should contain 'SSLHonorCipherOrder     test' }
      it { should contain 'SSLVerifyClient         test' }
      it { should contain 'SSLVerifyDepth          test' }
      it { should contain 'SSLOptions test test1' }
    end
  end

end
