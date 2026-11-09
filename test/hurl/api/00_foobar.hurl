# Check endpoint which does not exist
GET http://{{hostnport}}/api/index.php/foobar
HTTP 501
