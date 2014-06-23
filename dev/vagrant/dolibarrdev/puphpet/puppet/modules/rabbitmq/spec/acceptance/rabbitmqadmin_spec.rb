require 'spec_helper_acceptance'

describe 'rabbitmq::install::rabbitmqadmin class' do
  context 'does nothing if service is unmanaged' do
    it 'should run successfully' do
      pp = <<-EOS
      class { 'rabbitmq':
        admin_enable   => true,
        service_manage => false,
      }
      if $::osfamily == 'RedHat' {
        class { 'erlang': epel_enable => true}
        Class['erlang'] -> Class['rabbitmq']
      }
      EOS

      shell('rm -f /var/lib/rabbitmq/rabbitmqadmin')
      apply_manifest(pp, :catch_failures => true)
    end

    describe file('/var/lib/rabbitmq/rabbitmqadmin') do
      it { should_not be_file }
    end
  end

  context 'downloads the cli tools' do
    it 'should run successfully' do
      pp = <<-EOS
      class { 'rabbitmq':
        admin_enable   => true,
        service_manage => true,
      }
      if $::osfamily == 'RedHat' {
        class { 'erlang': epel_enable => true}
        Class['erlang'] -> Class['rabbitmq']
      }
      EOS

      apply_manifest(pp, :catch_failures => true)
    end

    describe file('/var/lib/rabbitmq/rabbitmqadmin') do
      it { should be_file }
    end
  end
end
