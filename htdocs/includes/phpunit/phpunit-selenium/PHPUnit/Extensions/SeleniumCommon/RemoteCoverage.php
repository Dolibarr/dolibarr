<?php
class PHPUnit_Extensions_SeleniumCommon_RemoteCoverage
{
    public function __construct($coverageScriptUrl, $testId)
    {
        $this->coverageScriptUrl = $coverageScriptUrl;
        $this->testId = $testId;
    }

    public function get()
    {
        if (!empty($this->coverageScriptUrl)) {
            $url = sprintf(
              '%s?PHPUNIT_SELENIUM_TEST_ID=%s',
              $this->coverageScriptUrl,
              urlencode($this->testId)
            );

            $buffer = @file_get_contents($url);

            if ($buffer !== FALSE) {
                $coverageData = unserialize($buffer);
                if (is_array($coverageData)) {
                    return $this->matchLocalAndRemotePaths($coverageData);
                } else {
                    throw new Exception('Empty or invalid code coverage data received from url "' . $url . '" (' . var_export($buffer, true) . ')');
                }
            }
        }

        return array();
    }

    /**
     * @param  array $coverage
     * @return array
     * @author Mattis Stordalen Flister <mattis@xait.no>
     */
    protected function matchLocalAndRemotePaths(array $coverage)
    {
        $coverageWithLocalPaths = array();

        foreach ($coverage as $originalRemotePath => $data) {
            $remotePath = $originalRemotePath;
            $separator  = $this->findDirectorySeparator($remotePath);

            while (!($localpath = stream_resolve_include_path($remotePath)) &&
                   strpos($remotePath, $separator) !== FALSE) {
                $remotePath = substr($remotePath, strpos($remotePath, $separator) + 1);
            }

            if ($localpath && md5_file($localpath) == $data['md5']) {
                $coverageWithLocalPaths[$localpath] = $data['coverage'];
            }
        }

        return $coverageWithLocalPaths;
    }

    /**
     * @param  string $path
     * @return string
     * @author Mattis Stordalen Flister <mattis@xait.no>
     */
    protected function findDirectorySeparator($path)
    {
        if (strpos($path, '/') !== FALSE) {
            return '/';
        }

        return '\\';
    }
}
