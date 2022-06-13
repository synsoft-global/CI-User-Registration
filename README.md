## This is just a REST APIs demonstration with CodeIgniter 4

# PHP Composer should be installed to set it up on your local. 
```
Run: composer install
```
# It have follwing REST APIs
```
api/user/register -> post(method) -> firstname & lastname & email & password

api/user/login -> post(method) -> email & password

api/user/forgotpassword -> post(method) -> email

api/user/resetpassword -> post(method) -> email with Authorization Header(Bearer Token)

api/user/profile -> get (method) -> Authorization Header(Bearer Token)

api/user/verify_email/{token} -> get (method) From Verification email.
```
