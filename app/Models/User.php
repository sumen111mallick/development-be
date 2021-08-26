<?php

namespace App\Models;

use Laravel\Passport\HasApiTokens;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
		'last_name',
		'userSelect_type',
        'email',
        'usertype',
        'profile_pic',
        'company_name',
        'company_url',
        'address',
        'city',
        'other_mobile_number',
        'landline_number',
        'company_profile',
        'pan_number',
        'aadhar_number',
        'provided_service',
        'place_of_practice',
        'price_for_service',
        'law_firm_number',
        'practice_number',
        'password',
        'user_role',
        'internal_user',
        'branch',
        'area_names'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];


}
