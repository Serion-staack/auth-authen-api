<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enum\UserTypesEnum;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens,SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'full_name',
        'email',
        'notes',
        'address',
        'verification_code',
        'verification_expired_at',
        'role_id',
        'user_type',
        'phone_number',
        'password',
        'email_verified_at',
        'google_id',
        'approved_google_login',
        'alert_login',
        'last_login',
        'login_code',
        'login_code_expires_at',
        'login_attempts',
        'login_blocked_until',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'login_code',
        'login_code_expires_at',
        'verification_code',
        'verification_code_expired_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function sendPasswordResetNotification($token)
    {
      /*  $this->notify(new ResetPassword($token));             //kjo eshte vec per web*/

        $this->notify(new ResetPasswordNotification($token));  //custom notification kur perdor API

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

  /*  public function hasVerifiedEmail()
    {
        return ! is_null($this->email_verified_at);
    }*/

}
