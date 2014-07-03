require 'spec_helper'

describe 'elasticsearch::python', :type => 'define' do

  let(:facts) { {:operatingsystem => 'CentOS' }}

  [ 'pyes', 'rawes', 'pyelasticsearch', 'ESClient', 'elasticutils', 'elasticsearch' ].each do |pythonlib|

    context "installation of library #{pythonlib}" do

      let(:title) { pythonlib }

      it { should contain_package(pythonlib).with(:provider => 'pip') }

    end

  end

end
