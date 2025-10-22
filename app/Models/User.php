<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enum\UserTypesEnum;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'notes',
        'address',
        'role_id',
        'user_type',
        'phone_number',
        'password',

    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    /*public function IsAdmin() : bool
    {
        return $this->user_type === UserTypesEnum::ADMIN;

    }
    public function IsSubject() : bool
    {
        return $this->user_type === UserTypesEnum::SUBJECT;
    }
    public function IsStaff() : bool
    {
        return $this->user_type === UserTypesEnum::STAFF;
    }*/

    public function hasVerifiedEmail()
    {
        return ! is_null($this->email_verified_at);
    }

}
