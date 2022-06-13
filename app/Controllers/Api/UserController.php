<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class UserController extends BaseController
{
    use ResponseTrait;
    /**
     * get user profile
     * profile()
     * taken authorization token in header,
     */
    public function index()
    {

        $key = getenv('TOKEN_SECRET');
        $header = $this->request->getServer('HTTP_AUTHORIZATION');

        if (!$header) return $this->failUnauthorized('Token Required');
        $token = explode(' ', $header)[1];
        try {
            /** token decode */
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            $user = new User();
            /* get User */
            $userdata = $user->find($decoded->id);
            unset($userdata['password']);
            unset($userdata['deleted_at']);
            return $this->respond(['status' => true, 'data' => $userdata, 'message' => 'Successfully done.'], 200);
        } catch (\Throwable $th) {
            return $this->fail('Invalid Token');
        }
    }
}
