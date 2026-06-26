<?php

namespace App\Models;

use CodeIgniter\Model;

class VisitLogModel extends Model
{
    protected $table         = 'visit_logs';
    protected $primaryKey    = 'id';
    protected $useTimestamps = false;
    protected $allowedFields = ['user_id', 'browser_id', 'ip', 'user_agent', 'referer', 'created_at'];

    // 같은 날 재방문 시 UPDATE 허용 최소 간격 (분)
    private const UPDATE_THRESHOLD_MINUTES = 30;

    public function recordVisit(string $ip, ?string $browserId, ?string $userAgent, ?string $referer, ?string $userId = null): void
    {
        $today    = date('Y-m-d');
        $existing = $this->where('ip', $ip)
            ->where("DATE(created_at) = " . $this->db->escape($today))
            ->orderBy('created_at', 'DESC')
            ->first();

        if ($existing === null) {
            $this->insert([
                'user_id'    => $userId,
                'browser_id' => $browserId,
                'ip'         => $ip,
                'user_agent' => $userAgent,
                'referer'    => $referer,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            return;
        }

        $threshold = time() - (self::UPDATE_THRESHOLD_MINUTES * 60);
        if (strtotime($existing['created_at']) > $threshold) {
            if ($userId !== null && empty($existing['user_id'])) {
                $this->where('id', $existing['id'])->set('user_id', $userId)->update();
            }
            return;
        }

        $updateData = ['created_at' => date('Y-m-d H:i:s')];
        if ($userId !== null) {
            $updateData['user_id'] = $userId;
        }
        $this->where('id', $existing['id'])->set($updateData)->update();
    }
}
