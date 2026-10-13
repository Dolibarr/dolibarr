# Check status
GET http://{{hostnport}}/
HTTP 200
[Captures]
token: xpath "normalize-space(//meta[@name='anti-csrf-newtoken']/@content)"

# Check status
POST http://{{hostnport}}/index.php?token={{token}}&actionlogin=login&loginfunction=loginfunction&username={{username}}&password={{password}}
HTTP 200
