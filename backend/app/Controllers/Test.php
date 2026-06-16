<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use Config\Database;

class Test extends Controller
{
    public function db()
    {
        try {
            $db = Database::connect();
            $query = $db->query("SELECT 1 as result");
            $result = $query->getRow();

            return $this->response->setJSON([
                'status' => 'success',
                'result' => $result->result
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
}
