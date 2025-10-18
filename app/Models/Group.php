<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;

    public function groupcontacts()
    {
        return $this->hasMany(Groupcontact::class);
    }

    public function contacts()
    {
        return $this->belongsToMany(Contact::class,'groupcontacts','group_id','contact_id')->select('name','phone');
    }
}

