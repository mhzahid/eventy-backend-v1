<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'phone', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasUlids, HasFactory, Notifiable, SoftDeletes, HasRoles;

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


    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function organizerProfile()
    {
        return $this->hasOne(OrganizerProfile::class);
    }
    public function organizerOwnerInfo()
    {
        return $this->hasOne(OrganizerOwnerInfo::class);
    }
    public function organizerBanking()
    {
        return $this->hasOne(OrganizerBanking::class);
    }

    public function organizerBalance()
    {
        return $this->hasOne(OrganizerBalance::class);
    }

    public function withdrawals()
    {
        return $this->hasMany(Withdrawal::class);
    }

    public function managedScanners()
    {
        return $this->hasMany(OrganizerScanner::class, 'organizer_id');
    }

    public function scannerProfile()
    {
        return $this->hasOne(OrganizerScanner::class, 'scanner_id');
    }



    // Accessor
    protected function organizerData(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->hasRole('organizer') ? [
                'profile'    => $this->organizerProfile,
                'owner_info' => $this->organizerOwnerInfo,
                'banking'    => $this->organizerBanking,
            ] : null,
        );
    }

    protected function organizerProfileData(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->hasRole('organizer') ? $this->organizerProfile : null,
        );
    }

    // protected function organizerBalanceData(): Attribute
    // {
    //     return Attribute::make(
    //         get: fn() => $this->hasRole('organizer') ? $this->organizerBalance : null,
    //     );
    // }

    protected function withdrawalsData(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->hasRole('organizer') ? $this->withdrawals : null,
        );
    }
}