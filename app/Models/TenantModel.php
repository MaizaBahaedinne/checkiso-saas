<?php

namespace App\Models;

use CodeIgniter\Model;

class TenantModel extends Model
{
    protected $table            = 'tenants';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'name', 'slug', 'status',
        'sector', 'employees_range',
        'address_line', 'city', 'postal_code', 'country_code',
        'website', 'contact_email',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    /**
     * Searches tenants by name (for autocomplete / duplicate detection).
     * Returns up to $limit results.
     */
    public function search(string $term, int $limit = 10): array
    {
        return $this->like('name', $term)
                    ->where('status', 'active')
                    ->orderBy('name', 'ASC')
                    ->findAll($limit);
    }

    /**
     * Checks whether a tenant with a very similar name already exists
     * (case-insensitive exact match).
     */
    public function findByName(string $name): ?array
    {
        return $this->where('LOWER(name)', mb_strtolower($name))->first();
    }
}
