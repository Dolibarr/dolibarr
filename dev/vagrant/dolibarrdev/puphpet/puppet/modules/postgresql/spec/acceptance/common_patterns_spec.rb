require 'spec_helper_acceptance'

describe 'common patterns:', :unless => UNSUPPORTED_PLATFORMS.include?(fact('osfamily')) do
  describe 'postgresql.conf include pattern' do
    after :all do
      pp = <<-EOS.unindent
        class { 'postgresql::server': ensure => absent }

        file { '/tmp/include.conf':
          ensure => absent
        }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    it "should support an 'include' directive at the end of postgresql.conf" do
      pending('no support for include directive with centos 5/postgresql 8.1',
        :if => (fact('osfamily') == 'RedHat' and fact('lsbmajdistrelease') == '5'))

      pp = <<-EOS.unindent
        class { 'postgresql::server': }

        $extras = "/etc/postgresql-include.conf"

        file { $extras:
          content => 'max_connections = 123',
          seltype => 'postgresql_db_t',
          seluser => 'system_u',
          notify  => Class['postgresql::server::service'],
        }

        postgresql::server::config_entry { 'include':
          value   => $extras,
          require => File[$extras],
        }
      EOS

      apply_manifest(pp, :catch_failures => true)
      apply_manifest(pp, :catch_changes => true)

      psql('--command="show max_connections" -t', 'postgres') do |r|
        expect(r.stdout).to match(/123/)
        expect(r.stderr).to eq('')
      end
    end
  end
end
