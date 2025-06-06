<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPreferredMatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'relationship_type',
        'gender',
        'age_range',
        'height_range',
        'weight_range',
        'language_spoken',
        'marital_status',
        'ethnicity',
        'genotype',
        'nationality',
        'state',
    ];

    protected $casts = [
        'gender' => 'array',
        'age_range' => 'array',
        'height_range' => 'array',
        'weight_range' => 'array',
        'language_spoken' => 'array',
        'marital_status' => 'array',
        'ethnicity' => 'array',
        'genotype' => 'array',
        'nationality' => 'array',
        'state' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function religions()
    {
        return $this->hasMany(UserPreferredMatchReligion::class);
    }
}
