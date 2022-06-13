<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Models\User;
use App\Models\ResetPassword;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class ResetPasswordController extends BaseController
{
    use ResponseTrait;

    /**
     * forgotpassword()
     * @param email string
     */
    public function forgotpassword()
    {
        /** Valdiation rules and messages */
        $rules = [
            "email" => "required|valid_email|min_length[6]",
        ];
        $messages = [
            "email" => [
                "required" => "Email is required.",
                "valid_email" => "Email address is not in format.",
                "is_unique" => "Email address already exists."
            ]
        ];
        if (!$this->validate($rules, $messages)) {
            return $this->respond(['status' => false, 'message' => $this->validator->getErrors()], 400);
        } else {
            $form_data['email'] = $this->request->getVar("email");
            $user = new User();
            /** Check Email exist or not */
            $existEmail = $user->where(['email' => $form_data['email'], 'deleted_at' => null])->first();
            if ($existEmail) {
                $key = getenv('TOKEN_SECRET');
                $payload = array(
                    "iat" => time(),
                    "exp" => time() + 900,
                    "id" => $existEmail['id'],
                    "email" => $existEmail['email']
                );
                /** Generate token  */
                $token = JWT::encode($payload, $key, 'HS256');
                $ResetPassword = new ResetPassword();
                $save = $ResetPassword->save(['user_id' => $existEmail['id'], 'email' => $existEmail['email'], 'token' => $token]);
                $url =  'http://localhost:8080/api/user/resetpassword/' . $token;
                /** email sending */
                $to = $existEmail['email'];
                $subject = 'Reset Password Link';
                $message = '<p>Hi,</p>
                <p>Reset Password Link is <a href="' . $url . '" target="_blank"></a></p>';

                $email = \Config\Services::email();
                $email->setTo($to);
                $email->setFrom('vasutamrakar.synsoft@gmail.com', 'Email Verify');

                $email->setSubject($subject);
                $email->setMessage($message);
                if ($email->send()) {
                    return $this->respond(['status' => true, 'message' => 'Reset password sent on your email.'], 200);
                } else {
                    $data = $email->printDebugger(['headers']);
                    return $this->respond(['status' => true, 'message' => $data], 500);
                }
            } else {
                $this->respond(['status' => false, 'message' => 'Email address not found.'], 400);
            }
        }
    }

    /**
     * resetpassword()
     * @param token string
     * @param newpassword string
     * @param cnfpassword string
     */
    public function resetpassword()
    {
        $rules = [
            "token" => "required",
            "newpassword" => "required|min_length[6]",
            "cnfpassword" => "required|matches[newpassword]"
        ];

        $messages = [
            "token" => [
                "required" => "Token is required."
            ],
            "newpassword" => [
                "required" => "newpassword is required."
            ],
            "cnfpassword" => [
                "required" => "cnfpassword is required."
            ]
        ];

        if (!$this->validate($rules, $messages)) {
            return $this->respond(['status' => false, 'message' => $this->validator->getErrors()], 400);
        } else {
            $token = $this->request->getVar("token");
            $new_pass = md5($this->request->getVar("newpassword"));
            $key = getenv('TOKEN_SECRET');
            try {
                /** decode token */
                $decoded = JWT::decode($token, new Key($key, 'HS256'));
                if ($decoded) {
                    $ResetPassword = new ResetPassword();
                    $user = new user();
                    // /** Check Email exist or not */
                    $existEmail = $ResetPassword->where(['email' => $decoded->email, 'token' => $token])->first();
                    if ($existEmail) {
                        /** update user password */
                        $updated = $user->update($existEmail['user_id'], ['password' => $new_pass, 'updated_at' => date('Y-m-d H:i:s')]);
                        $ResetPassword->where('email', $existEmail['email'])->delete();
                        if ($updated) {
                            return $this->respond(['status' => true, 'message' => 'Password reset successfully.'], 200);
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
}
