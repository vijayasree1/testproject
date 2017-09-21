<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class ManufacturerSystem extends Model
{
    protected $table = 'manufacturer_system';

    protected $primaryKey = 'Idx';

    public function manufacturer()
    {
        return $this->belongsTo('App\Http\Models\Manufacturer', 'Manufacturer_Idx');
    }

    public function terminal()
    {
        return $this->belongsTo('App\Http\Models\ManufacturerTerminal', 'Manufacturer_System_Idx');
    }
}