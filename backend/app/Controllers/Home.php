<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index(): string
    {
        return view('chat', [
            'loggedInUserId' => session()->get('user_id'),
        ]);
    }
}
