<?php

namespace App\Models;

use CodeIgniter\Model;

class MembershipModel extends Model
{
    protected $table            = 'memberships';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'tenant_id',
        'user_id',
        'company_id',
        'org_unit_id',
        'status',
        'is_default',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Returns the active default membership for a user (used to resolve tenant context at login).
     */
    public function getDefaultForUser(int $userId): ?array
    {
        return $this->where('user_id', $userId)
                    ->where('is_default', 1)
                    ->where('status', 'active')
                    ->first();
    }
}
