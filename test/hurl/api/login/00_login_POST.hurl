# POST Login should fail
POST http://{{hostnport}}/api/index.php/login
{
    "login" : "foo",
    "password" : "bar"
}
HTTP 403
