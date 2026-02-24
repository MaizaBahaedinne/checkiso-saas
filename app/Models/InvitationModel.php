<?php

namespace App\Models;

use CodeIgniter\Model;

class InvitationModel extends Model
{
    protected $table            = 'org_invitations';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'tenant_id', 'invited_by', 'email', 'role_code',
        'token', 'status', 'expires_at', 'accepted_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /** Find a valid (pending, not expired) invitation by token. */
    public function findValid(string $token): ?array
    {
        return $this->where('token', $token)
                    ->where('status', 'pending')
                    ->groupStart()
                        ->where('expires_at IS NULL')
                        ->orWhere('expires_at >', date('Y-m-d H:i:s'))
                    ->groupEnd()
                    ->first();
    }

    /** All pending/accepted invitations for a tenant (admin view). */
    public function forTenant(int $tenantId): array
    {
        return $this->select('org_invitations.*, u.first_name, u.last_name')
                    ->join('users u', 'u.id = org_invitations.invited_by', 'left')
                    ->where('org_invitations.tenant_id', $tenantId)
                    ->orderBy('org_invitations.created_at', 'DESC')
                    ->findAll();
    }

    /** Generates a cryptographically secure unique token. */
    public static function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}
