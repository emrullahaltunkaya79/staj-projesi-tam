<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\RecordsActivity;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use BelongsToCompany, RecordsActivity;

    protected $fillable = ['company_id', 'name'];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
