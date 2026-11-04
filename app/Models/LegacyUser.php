<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class LegacyUser extends Authenticatable
{
    use Notifiable;

    protected $table = 'users';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'data',
        'email',
        'role',
        'superior_id',
        'fullname',
    ];

    protected $hidden = [
        'data',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }

    public function getAuthPassword(): string
    {
        return (string) ($this->data['laravel_password_hash'] ?? $this->data['password_hash'] ?? '');
    }

    public function getLegacyPasswordHash(): string
    {
        return (string) ($this->data['password_hash'] ?? '');
    }

    public function getRoleAttribute(): ?string
    {
        return $this->data['role'] ?? $this->attributes['role'] ?? null;
    }

    public function getFullnameAttribute(): ?string
    {
        return $this->data['fullname'] ?? $this->attributes['fullname'] ?? null;
    }

    public function getEmailAttribute(): ?string
    {
        return $this->data['email'] ?? $this->attributes['email'] ?? null;
    }

    public function getSuperiorIdAttribute(): ?string
    {
        return $this->data['superior_id'] ?? $this->attributes['superior_id'] ?? null;
    }

    public function getBagianAttribute(): ?string
    {
        return $this->data['bagian'] ?? null;
    }

    public function getJabatanAtasanAttribute(): ?string
    {
        return $this->data['jabatan_atasan'] ?? null;
    }

    public function getDispPantauAttribute(): ?bool
    {
        return $this->data['disp_pantau'] ?? null;
    }

    public function getDispKirimAttribute(): ?bool
    {
        return $this->data['disp_kirim'] ?? null;
    }

    public function toLegacyArray(): array
    {
        return $this->data ?? [];
    }

    /**
     * Simpan perubahan ke kolom JSON sekaligus kolom indeks yang dipakai
     * adapter MySQL lama (db_mysql.py).
     */
    public function syncIndexColumns(): static
    {
        $data = $this->data ?? [];

        $this->attributes['email'] = $data['email'] ?? null;
        $this->attributes['role'] = $data['role'] ?? null;
        $this->attributes['superior_id'] = $data['superior_id'] ?? null;
        $this->attributes['fullname'] = $data['fullname'] ?? null;

        return $this;
    }

    public function save(array $options = []): bool
    {
        $this->syncIndexColumns();

        return parent::save($options);
    }
}
