require 'spec_helper_acceptance'

describe 'rabbitmq with delete_guest_user' do
  context 'delete_guest_user' do
    it 'should run successfully' do
      pp = <<-EOS
      class { 'rabbitmq': 
        port              => '5672',
        delete_guest_user => true,
      }
      if $::osfamily == 'RedHat' {
        class { 'erlang': epel_enable => true}
        Class['erlang'] -> Class['rabbitmq']
      }
      EOS

      apply_manifest(pp, :catch_failures => true)
      shell('rabbitmqctl list_users > /tmp/rabbitmqctl_users')
    end

    describe file('/tmp/rabbitmqctl_users') do
      it { should be_file }
      it { should_not contain 'guest' }
    end
  end
end
