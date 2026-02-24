<?php

namespace App\Models;

use CodeIgniter\Model;

class JoinRequestModel extends Model
{
    protected $table            = 'join_requests';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'tenant_id',
        'user_id',
        'status',
        'message',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Returns the pending request for this user+tenant combo (to avoid duplicates).
     */
    public function getPending(int $userId, int $tenantId): ?array
    {
        return $this->where('user_id', $userId)
                    ->where('tenant_id', $tenantId)
                    ->where('status', 'pending')
                    ->first();
    }

    /**
     * All pending requests for a given tenant (for admin review).
     */
    public function pendingForTenant(int $tenantId): array
    {
        return $this->select('join_requests.*, users.first_name, users.last_name, users.email')
                    ->join('users', 'users.id = join_requests.user_id')
                    ->where('join_requests.tenant_id', $tenantId)
                    ->where('join_requests.status', 'pending')
                    ->orderBy('join_requests.created_at', 'ASC')
                    ->findAll();
    }
}
