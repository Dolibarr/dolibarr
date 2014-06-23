$file      = '/etc/puppetlabs/foo.bar.tar.gz'
$filename  = staging_parse($file)
$basename  = staging_parse($file, 'basename')
$extname   = staging_parse($file, 'extname')
$parent    = staging_parse($file, 'parent')
$rbasename = staging_parse($file, 'basename', '.tar.gz')

notice($filename)
notice($basename)
notice($extname)
notice($parent)
notice($rbasename)
