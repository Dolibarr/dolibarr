require 'spec_helper_acceptance'

describe 'supervisord install' do

  context 'default parameters with pip and init install' do
    it 'should work with no errors' do
      pp = <<-EOS
        class { 'supervisord': install_pip  => true, install_init => true}
      EOS

      expect(apply_manifest(pp).exit_code).to_not eq(1)
      expect(apply_manifest(pp).exit_code).to eq(0)
    end

    describe service('supervisord') do
      it { should be_enabled }
      it { should be_running }
      it 'should restart successfully' do
        cmd="service supervisord restart"
        expect(shell(cmd).exit_code).to_not eq(1)
      end
    end
  end
end

describe 'supervisord::program' do
  context 'create a program config' do
    it 'should install a program file' do

      pp = <<-EOS
        include supervisord
        supervisord::program { 'test': 
          command => 'echo', 
          priority => '100', 
          environment => { 
            'HOME' => '/root',
            'PATH' => '/bin',
          }
        }
      EOS

      expect(apply_manifest(pp).exit_code).to_not eq(1)
      expect(apply_manifest(pp).exit_code).to eq(0)
    end

    it 'should contain the correct values' do
      cmd='grep command=echo /etc/supervisor.d/program_test.conf'
      expect(shell(cmd).exit_code).to eq(0)
      cmd='grep priority=100 /etc/supervisor.d/program_test.conf'
      expect(shell(cmd).exit_code).to eq(0)
      cmd='grep "environment=" /etc/supervisor.d/program_test.conf'
      expect(shell(cmd).exit_code).to eq(0)
    end
  end
end 

describe 'supervisord::fcgi-program' do
  context 'create fcgi-program config' do
    it 'should install a fcgi-program file' do

      pp = <<-EOS
        include supervisord
        supervisord::fcgi_program { 'test':
          socket  => 'tcp://localhost:1000',
          command => 'echo',
          priority => '100',
          environment => {
            'HOME' => '/root',
            'PATH' => '/bin',
          }
        }
      EOS

      expect(apply_manifest(pp).exit_code).to_not eq(1)
      expect(apply_manifest(pp).exit_code).to eq(0)
    end

    it 'should contain the correct values' do
      cmd='grep socket=tcp://localhost:1000 /etc/supervisor.d/fcgi-program_test.conf'
      expect(shell(cmd).exit_code).to eq(0)
      cmd="grep command=echo /etc/supervisor.d/fcgi-program_test.conf"
      expect(shell(cmd).exit_code).to eq(0)
      cmd="grep priority=100 /etc/supervisor.d/fcgi-program_test.conf"
      expect(shell(cmd).exit_code).to eq(0)
      cmd='grep "environment=" /etc/supervisor.d/fcgi-program_test.conf'
      expect(shell(cmd).exit_code).to eq(0)
    end
  end
end

describe 'supervisord::group' do
  context 'create group config' do
    it 'should install a group config' do

      pp = <<-EOS
        include supervisord
        supervisord::group { 'test':
          programs => ['test'],
          priority => '100',
        }
      EOS

      expect(apply_manifest(pp).exit_code).to_not eq(1)
      expect(apply_manifest(pp).exit_code).to eq(0)
    end

    it 'should contain the correct values' do
      cmd='grep "programs=test" /etc/supervisor.d/group_test.conf'
      expect(shell(cmd).exit_code).to eq(0)
      cmd="grep priority=100 /etc/supervisor.d/group_test.conf"
      expect(shell(cmd).exit_code).to eq(0)
    end
  end
end
