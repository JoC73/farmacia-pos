<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]

class User extends Authenticatable
{
    use HasFactory;
    use HasRoles;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'sucursal_id',
        'estado'
    ];

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function canViewAllSucursales(): bool
    {
        return $this->hasRole('Super Usuario');
    }

    public function visibleSucursalId(): ?int
    {
        return $this->canViewAllSucursales()
            ? null
            : $this->sucursal_id;
    }

    public function canAccessSucursal(?int $sucursalId): bool
    {
        return $this->canViewAllSucursales()
            || ((int) $this->sucursal_id === (int) $sucursalId);
    }
}
