# POST Login should fail
POST http://localhost:8080/api/index.php/login
{
    "login" : "foo",
    "password" : "bar"
}
HTTP 403
