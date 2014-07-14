require 'spec_helper'

describe 'supervisord::program', :type => :define do
  let(:title) {'foo'}
  let(:facts) {{ :concat_basedir => '/var/lib/puppet/concat' }}
  let(:default_params) do 
    {
      :command                 => 'bar',
      :process_name            => '%(process_num)s',
      :numprocs                => '1',
      :numprocs_start          => '0',
      :priority                => '999',
      :autostart               => true,
      :autorestart             => 'unexpected',
      :startsecs               => '1',
      :startretries            => '3',
      :exitcodes               => '0,2',
      :stopsignal              => 'TERM',
      :stopwaitsecs            => '10',
      :stopasgroup             => true,
      :killasgroup             => true,
      :user                    => 'baz',
      :redirect_stderr         => true,
      :stdout_logfile          => 'program_foo.log',
      :stdout_logfile_maxbytes => '50MB',
      :stdout_logfile_backups  => '10',
      :stdout_capture_maxbytes => '0',
      :stdout_events_enabled   => true,
      :stderr_logfile          => 'program_foo.error',
      :stderr_logfile_maxbytes => '50MB',
      :stderr_logfile_backups  => '10',
      :stderr_capture_maxbytes => '0',
      :stderr_events_enabled   => true,
      :environment             => { 'env1' => 'value1', 'env2' => 'value2' },
      :directory               => '/opt/supervisord/chroot',
      :umask                   => '022',
      :serverurl               => 'AUTO'
    }
  end

  context 'default' do
    let(:params) { default_params }

    it { should contain_supervisord__program('foo') }
    it { should contain_file('/etc/supervisor.d/program_foo.conf').with_content(/\[program:foo\]/) }
    it { should contain_file('/etc/supervisor.d/program_foo.conf').with_content(/command=bar/) }
    it { should contain_file('/etc/supervisor.d/program_foo.conf').with_content(/process_name=\%\(process_num\)s/) }
    it { should contain_file('/etc/supervisor.d/program_foo.conf').with_content(/numprocs=1/) }
    it { should contain_file('/etc/supervisor.d/program_foo.conf').with_content(/numprocs_start=0/) }
    it { should contain_file('/etc/supervisor.d/program_foo.conf').with_content(/priority=999/) }
    it { should contain_file('/etc/supervisor.d/program_foo.conf').with_content(/autostart=true/) }
    it { should contain_file('/etc/supervisor.d/program_foo.conf').with_content(/startsecs=1/) }
    it { should contain_file('/etc/supervisor.d/program_foo.conf').with_content(/startretries=3/) }
    it { should contain_file('/etc/supervisor.d/program_foo.conf').with_content(/exitcodes=0,2/) }
    it { should contain_file('/etc/supervisor.d/program_foo.conf').with_content(/stopsignal=TERM/) }
    it { should contain_file('/etc/supervisor.d/program_foo.conf').with_content(/stopwaitsecs=10/) }
    it { should contain_file('/etc/supervisor.d/program_foo.conf').with_content(/stopasgroup=true/) }
    it { should contain_file('/etc/supervisor.d/program_foo.conf').with_content(/killasgroup=true/) }
    it { should contain_file('/etc/supervisor.d/program_foo.conf').with_content(/user=baz/) }
    it { should contain_file('/etc/supervisor.d/program_foo.conf').with_content(/redirect_stderr=true/) }
    it { should contain_file('/etc/supervisor.d/program_foo.conf').with_content(/stdout_logfile=\/var\/log\/supervisor\/program_foo.log/) }
    it { should contain_file('/etc/supervisor.d/program_foo.conf').with_content(/stdout_logfile_maxbytes=50MB/) }
    it { should contain_file('/etc/supervisor.d/program_foo.conf').with_content(/stdout_logfile_backups=10/) }
    it { should contain_file('/etc/supervisor.d/program_foo.conf').with_content(/stdout_capture_maxbytes=0/) }
    it { should contain_file('/etc/supervisor.d/program_foo.conf').with_content(/stdout_events_enabled=true/) }
    it { should contain_file('/etc/supervisor.d/program_foo.conf').with_content(/stderr_logfile=\/var\/log\/supervisor\/program_foo.error/) }
    it { should contain_file('/etc/supervisor.d/program_foo.conf').with_content(/stderr_logfile_maxbytes=50MB/) }
    it { should contain_file('/etc/supervisor.d/program_foo.conf').with_content(/stderr_logfile_backups=10/) }
    it { should contain_file('/etc/supervisor.d/program_foo.conf').with_content(/stderr_capture_maxbytes=0/) }
    it { should contain_file('/etc/supervisor.d/program_foo.conf').with_content(/stderr_events_enabled=true/) }
    it { should contain_file('/etc/supervisor.d/program_foo.conf').with_content(/environment=env1='value1',env2='value2'/) }
    it { should contain_file('/etc/supervisor.d/program_foo.conf').with_content(/directory=\/opt\/supervisord\/chroot/) }
    it { should contain_file('/etc/supervisor.d/program_foo.conf').with_content(/umask=022/) }
    it { should contain_file('/etc/supervisor.d/program_foo.conf').with_content(/serverurl=AUTO/) }
  end

  context 'ensure_process_stopped' do
    let(:params) { default_params.merge({ :ensure_process => 'stopped' }) }
    it { should contain_supervisord__supervisorctl('stop_foo') }
  end

  context 'ensure_process_removed' do
    let(:params) { default_params.merge({ :ensure_process => 'removed' }) }
    it { should contain_supervisord__supervisorctl('remove_foo') }
  end
end
