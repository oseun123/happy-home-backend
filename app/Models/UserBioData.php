<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class UserBioData extends Model implements AuditableContract
{
    use HasFactory, Auditable;

    protected $table = 'user_bio_data';

    protected $fillable = [
        'user_id',
        'height_range',
        'weight_range',
        'ethnicity',
        'genotype',
        'marital_status',
        'occupation'
    ];

    public function religions()
    {
        return $this->hasMany(UserReligion::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
