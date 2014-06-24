require 'spec_helper'

describe 'nginx::resource::upstream' do
  let :title do
    'upstream-test'
  end

  let :default_params do
    {
      :members => ['test'],
    }
  end

  describe 'os-independent items' do

    describe 'basic assumptions' do
      let :params do default_params end

      it { should contain_file("/etc/nginx/conf.d/#{title}-upstream.conf").with(
        {
          'owner'   => 'root',
          'group'   => 'root',
          'mode'    => '0644',
          'ensure'  => 'file',
          'content' => /upstream #{title}/,
        }
      )}
    end

    describe "upstream.conf template content" do
      [
        {
          :title => 'should contain ordered prepended directives',
          :attr  => 'upstream_cfg_prepend',
          :value => {
            'test3' => 'test value 3',
            'test1' => 'test value 1',
            'test2' => 'test value 2',
            'test4' => ['test value 1', 'test value 2'],
            'test5' => {'subkey1' => 'subvalue1'},
            'test6' => {'subkey1' => ['subvalue1', 'subvalue2']},
          },
          :match => [
            '  test1 test value 1;',
            '  test2 test value 2;',
            '  test3 test value 3;',
            '  test4 test value 1;',
            '  test4 test value 2;',
            '  test5 subkey1 subvalue1;',
            '  test6 subkey1 subvalue1;',
            '  test6 subkey1 subvalue2;',
          ],
        },
        {
          :title => 'should set server',
          :attr  => 'members',
          :value => [
            'test3',
            'test1',
            'test2',
          ],
          :match => [
            '  server     test3  fail_timeout=10s;',
            '  server     test1  fail_timeout=10s;',
            '  server     test2  fail_timeout=10s;',
          ],
        },
      ].each do |param|
        context "when #{param[:attr]} is #{param[:value]}" do
          let :params do default_params.merge({ param[:attr].to_sym => param[:value] }) end

          it { should contain_file("/etc/nginx/conf.d/#{title}-upstream.conf").with_mode('0644') }
          it param[:title] do
            verify_contents(subject, "/etc/nginx/conf.d/#{title}-upstream.conf", Array(param[:match]))
            Array(param[:notmatch]).each do |item|
              should contain_file("/etc/nginx/conf.d/#{title}-upstream.conf").without_content(item)
            end
          end
        end
      end

      context 'when ensure => absent' do
        let :params do default_params.merge(
          {
            :ensure => 'absent'
          }
        ) end

        it { should contain_file("/etc/nginx/conf.d/#{title}-upstream.conf").with_ensure('absent') }
      end
    end
  end
end
