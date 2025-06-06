<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserReligion extends Model
{
    use HasFactory;

    protected $fillable = ['user_bio_data_id', 'religion', 'denomination'];

    public function userBioData()
    {
        return $this->belongsTo(UserBioData::class);
    }
}
