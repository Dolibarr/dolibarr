require "#{File.join(File.dirname(__FILE__),'..','spec_helper.rb')}"

describe 'url_parse' do

  describe 'Test Url Components parsing' do
    it 'should return correct scheme' do
      should run.with_params('ftp://www.example.com/test','scheme').and_return('ftp') 
    end
    it 'should return correct userinfo' do
      should run.with_params('https://my_user:my_pass@www.example.com:8080/path/to/file.php?id=1&ret=0','userinfo').and_return('my_user:my_pass') 
    end
    it 'should return correct user' do
      should run.with_params('https://my_user:my_pass@www.example.com:8080/path/to/file.php?id=1&ret=0','user').and_return('my_user') 
    end
    it 'should return correct password' do
      should run.with_params('https://my_user:my_pass@www.example.com:8080/path/to/file.php?id=1&ret=0','password').and_return('my_pass') 
    end
    it 'should return correct host' do
      should run.with_params('https://my_user:my_pass@www.example.com:8080/path/to/file.php?id=1&ret=0','host').and_return('www.example.com') 
    end
    it 'should return correct port' do
      should run.with_params('https://my_user:my_pass@www.example.com:8080/path/to/file.php?id=1&ret=0','port').and_return(8080) 
    end
    it 'should return correct path' do
      should run.with_params('https://my_user:my_pass@www.example.com:8080/path/to/file.php?id=1&ret=0','path').and_return('/path/to/file.php') 
    end
    it 'should return correct query' do
      should run.with_params('https://my_user:my_pass@www.example.com:8080/path/to/file.php?id=1&ret=0','query').and_return('id=1&ret=0') 
    end
    it 'should return correct filename' do
      should run.with_params('https://my_user:my_pass@www.example.com:8080/path/to/file.php?id=1&ret=0','filename').and_return('file.php') 
    end
    it 'should return correct filetype' do
      should run.with_params('https://my_user:my_pass@www.example.com:8080/path/to/file.php?id=1&ret=0','filetype').and_return('.php') 
    end
    it 'should return correct filedir' do
      should run.with_params('https://my_user:my_pass@www.example.com:8080/path/to/file.php?id=1&ret=0','filedir').and_return('file') 
    end

  end

end

