<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\VisitLogModel;
use CodeIgniter\HTTP\ResponseInterface;

class AuthController extends BaseController
{
    public function register(): ResponseInterface
    {
        $model = new UserModel();

        $data = [
            'user_id'    => $this->request->getPost('user_id'),
            'password'   => $this->request->getPost('password'),
            'email'      => $this->request->getPost('email'),
            'name'       => $this->request->getPost('name'),
            'birth_date' => $this->request->getPost('birth_date'),
            'gender'     => $this->request->getPost('gender'),
            'phone'      => $this->request->getPost('phone'),
            'nickname'   => $this->request->getPost('nickname'),
        ];

        // 만 14세 미만 가입 차단
        $birthDate = strtotime($data['birth_date'] ?? '');
        if ($birthDate !== false) {
            $minAge = mktime(0, 0, 0, date('m', $birthDate), date('d', $birthDate), date('Y', $birthDate) + 14);
            if ($minAge > time()) {
                return $this->response->setJSON([
                    'success' => false,
                    'errors'  => ['birth_date' => '만 14세 미만은 가입할 수 없습니다.'],
                ]);
            }
        }

        if (!$model->validate($data)) {
            return $this->response->setJSON([
                'success' => false,
                'errors'  => $model->errors(),
            ]);
        }

        if (!$model->createUser($data)) {
            return $this->response->setJSON([
                'success' => false,
                'errors'  => ['general' => '회원가입 처리 중 오류가 발생했습니다.'],
            ]);
        }

        return $this->response->setJSON(['success' => true]);
    }

    public function login(): ResponseInterface
    {
        $userId   = $this->request->getPost('user_id');
        $password = $this->request->getPost('password');

        $user = (new UserModel())->findByUserId((string) $userId);

        if ($user === null) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '존재하지 않는 아이디입니다.',
            ]);
        }

        if (!password_verify((string) $password, $user['password'])) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '패스워드가 일치하지 않습니다.',
            ]);
        }

        $ip        = $this->request->getIPAddress();
        $userAgent = substr($this->request->getHeaderLine('User-Agent'), 0, 500) ?: null;
        $referer   = substr((string) $this->request->getServer('HTTP_REFERER'), 0, 500) ?: null;
        $browserId = $this->request->getCookie('rc_vid') ?: null;

        (new VisitLogModel())->recordVisit($ip, $browserId, $userAgent, $referer, $userId);

        session()->set('user_id', $userId);

        return $this->response->setJSON(['success' => true]);
    }
}
