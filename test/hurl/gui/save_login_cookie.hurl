# Get login page and extract CSRF token
GET http://{{hostnport}}/
HTTP 200
[Captures]
token: regex "name=\"token\" value=\"([a-zA-Z0-9]+)\""

# Send login as form-data in body
POST http://{{hostnport}}/index.php?mainmenu=home
[FormParams]
token: {{token}}
actionlogin: login
loginfunction: loginfunction
username: {{username}}
password: {{password}}
HTTP 200
[Asserts]
xpath "count(//div[@title='Enter login details'])" == 0

# Proof that session works
GET http://{{hostnport}}/index.php?mainmenu=home
HTTP 200
[Asserts]
xpath "count(//div[@title='Enter login details'])" == 0
