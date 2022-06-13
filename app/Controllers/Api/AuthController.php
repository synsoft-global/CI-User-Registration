<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthController extends BaseController
{
    use ResponseTrait;

    /** 
     * Register()  post method
     * @param firstname string
     * @param lastname string
     * @param email string
     * @param password string
     *  */
    public function register()
    {
        $rules = [
            "firstname" => "required",
            "lastname" => "required",
            "email" => "required|valid_email|is_unique[users.email]|min_length[6]",
            "password" => "required|min_length[6]",
        ];

        $messages = [
            "firstname" => [
                "required" => "First name is required."
            ],
            "email" => [
                "required" => "Email is required.",
                "valid_email" => "Email address is not in format.",
                "is_unique" => "Email address already exists."
            ],
            "password" => [
                "required" => "Passwod is required."
            ],
        ];

        if (!$this->validate($rules, $messages)) {
            return $this->respond(['status' => false, 'message' => $this->validator->getErrors()], 400);
        } else {
            $form_data['firstname'] = $this->request->getVar("firstname");
            $form_data['lastname'] = $this->request->getVar("lastname");
            $form_data['email'] = $this->request->getVar("email");
            $form_data['password'] = md5($this->request->getVar("password"));
            $user = new User();
            /** Check Email exist or not */
            $existEmail = $user->where(['email' => $form_data['email'], 'deleted_at' => null])->first();
            if ($existEmail) {
                $this->respond(['status' => false, 'message' => 'Email address already exists.'], 400);
            } else {
                /** Save new user  */
                $user = new User();
                $created = $user->save($form_data);
                if ($created) {
                    $key = getenv('TOKEN_SECRET');
                    $payload = array(
                        "iat" => time(),
                        "exp" => time() + 600,
                        "email" => $form_data['email']
                    );
                    $token = JWT::encode($payload, $key, 'HS256');
                    $url = 'http://localhost:8080/api/user/verify_email/' . $token;

                    /** email sending */
                    $to = $this->request->getVar('email');
                    $subject = 'Verify Email Link';
                    $message = '<p>Hi ' . ucfirst($form_data['firstname']) . ',</p>
                    <p>Verify Email <a href="' . $url . '" target="_blank"></a></p>';

                    $email = \Config\Services::email();
                    $email->setTo($to);
                    $email->setFrom('vasutamrakar.synsoft@gmail.com', 'Email Verify');

                    $email->setSubject($subject);
                    $email->setMessage($message);
                    if ($email->send()) {
                        return $this->respond(['status' => true, 'message' => 'Please verified the email.'], 200);
                    } else {
                        $data = $email->printDebugger(['headers']);
                        return $this->respond(['status' => true, 'message' => $data], 500);
                    }
                } else {
                    return $this->respond(['status' => false, 'message' => 'Operation failed.'], 500);
                }
            }
        }
    }


    /** 
     * login()  post method
     * @param email string
     * @param password string
     *  */
    public function login()
    {
        $rules = [
            "email" => "required|valid_email|min_length[6]",
            "password" => "required",
        ];

        $messages = [
            "email" => [
                "required" => "Email is required."
            ],
            "password" => [
                "required" => "Passwod is required."
            ],
        ];

        if (!$this->validate($rules, $messages)) {
            return $this->respond(['status' => false, 'message' => $this->validator->getErrors()], 400);
        } else {
            $form_data['email'] = $this->request->getVar("email");
            $form_data['password'] = md5($this->request->getVar("password"));
            $user = new User();
            $existEmail = $user->where(['email' => $form_data['email'], 'deleted_at' => null])->first();
            if ($existEmail) {
                /**  check password **/
                if ($existEmail['password'] === $form_data['password']) {
                    if ($existEmail['email_verify'] !== null) {
                        $user = $existEmail;
                        unset($user['deleted_at']);
                        unset($user['password']);
                        /** generate token */
                        $key = getenv('TOKEN_SECRET');
                        $payload = array(
                            "iat" => time(),
                            "exp" => time() + 3600,
                            "id" => $user['id'],
                            "email" => $user['email']
                        );
                        $token = JWT::encode($payload, $key, 'HS256');
                        // $decoded = JWT::decode($token, new Key($key, 'HS256'));                    
                        return $this->respond(['status' => true, 'data' => $user, 'token' => $token, 'message' => 'Successfully logged in.'], 200);
                    } else {
                        return $this->respond(['status' => false, 'message' => 'Email not verified.'], 400);
                    }
                } else {
                    return $this->respond(['status' => false, 'message' => 'Invalid Credentials.'], 400);
                }
            } else {
                return $this->respond(['status' => false, 'message' => 'Email not found.'], 400);
            }
        }
    }

    /**
     * verify_email() get method by url
     */
    public function verify_email($token)
    {
        if (!$token) {
            return $this->respond(['status' => false, 'message' => 'Token is required.'], 400);
        }
        $key = getenv('TOKEN_SECRET');
        try {
            /** decode token */
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            if ($decoded) {
                $user = new user();
                // /** Check Email exist or not */
                $existEmail = $user->where(['email' => $decoded->email])->first();
                if ($existEmail) {
                    /** update user email_verify */
                    $updated = $user->update($existEmail['id'], ['email_verify' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
                    if ($updated) {
                        return $this->respond(['status' => true, 'message' => 'successfully verified.'], 200);
                    } else {
                        return $this->respond(['status' => false, 'message' => 'Process failed.'], 200);
                    }
                } else {
                    $this->respond(['status' => false, 'message' => 'Email address not found.'], 400);
                }
            }
        } catch (\Throwable $th) {
            return $this->fail('Invalid Token');
        }
    }
}
