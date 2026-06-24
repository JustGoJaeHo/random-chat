<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table         = 'users';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'user_id', 'password', 'email', 'name',
        'birth_date', 'gender', 'phone', 'nickname',
    ];

    protected $validationRules = [
        'user_id'    => 'required|min_length[6]|max_length[20]|regex_match[/^[a-z][a-z0-9]{5,19}$/]|is_unique[users.user_id]',
        'password'   => 'required|min_length[8]|max_length[16]',
        'email'      => 'required|valid_email|max_length[100]|is_unique[users.email]',
        'name'       => 'required|min_length[2]|max_length[20]|regex_match[/^[가-힣a-zA-Z]+$/]',
        'birth_date' => 'required|valid_date[Y-m-d]',
        'gender'     => 'required|in_list[M,F]',
        'phone'      => 'required|regex_match[/^010-\d{4}-\d{4}$/]|is_unique[users.phone]',
        'nickname'   => 'required|min_length[2]|max_length[16]|regex_match[/^[가-힣a-zA-Z0-9]+$/]|is_unique[users.nickname]',
    ];

    protected $validationMessages = [
        'user_id' => [
            'required'    => '아이디를 입력해주세요.',
            'min_length'  => '아이디는 6자 이상이어야 합니다.',
            'max_length'  => '아이디는 20자 이하여야 합니다.',
            'regex_match' => '아이디는 영문 시작 및 영문 소문자, 숫자만 사용 가능합니다.',
            'is_unique'   => '이미 사용 중인 아이디입니다.',
        ],
        'password' => [
            'required'   => '패스워드를 입력해주세요.',
            'min_length' => '패스워드는 8자 이상이어야 합니다.',
            'max_length' => '패스워드는 16자 이하여야 합니다.',
        ],
        'email' => [
            'required'    => '이메일을 입력해주세요.',
            'valid_email' => '유효하지 않은 이메일 형식입니다.',
            'max_length'  => '이메일은 100자 이하여야 합니다.',
            'is_unique'   => '이미 사용 중인 이메일입니다.',
        ],
        'name' => [
            'required'    => '이름을 입력해주세요.',
            'min_length'  => '이름은 2자 이상이어야 합니다.',
            'max_length'  => '이름은 20자 이하여야 합니다.',
            'regex_match' => '이름은 한글, 영문만 입력 가능합니다.',
        ],
        'birth_date' => [
            'required'   => '생년월일을 입력해주세요.',
            'valid_date' => '유효하지 않은 생년월일입니다.',
        ],
        'gender' => [
            'required' => '성별을 선택해주세요.',
            'in_list'  => '성별을 선택해주세요.',
        ],
        'phone' => [
            'required'    => '휴대전화번호를 입력해주세요.',
            'regex_match' => '휴대전화번호는 010-0000-0000 형식으로 입력해주세요.',
            'is_unique'   => '이미 사용 중인 휴대전화번호입니다.',
        ],
        'nickname' => [
            'required'    => '닉네임을 입력해주세요.',
            'min_length'  => '닉네임은 2자 이상이어야 합니다.',
            'max_length'  => '닉네임은 16자 이하여야 합니다.',
            'regex_match' => '닉네임은 한글, 영문, 숫자만 사용 가능합니다.',
            'is_unique'   => '이미 사용 중인 닉네임입니다.',
        ],
    ];

    public function createUser(array $data): bool
    {
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        // validate() 는 호출자(Controller)에서 이미 수행했으므로 재검사 스킵.
        // insert() 가 내부에서 다시 validate() 를 실행하면 bcrypt 해시(60자)가
        // max_length[16] 규칙에 걸려 실패하기 때문에 skipValidation 이 필요하다.
        return $this->skipValidation(true)->insert($data) !== false;
    }
}
