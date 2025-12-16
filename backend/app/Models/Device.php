<?php

namespace App\Models;

use App\Models\Alarm\DeviceSensorLogs;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Device extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function status()
    {
        return $this->belongsTo(DeviceStatus::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    public function companyBranch()
    {
        return $this->belongsTo(CompanyBranch::class, "branch_id");
    }
    public function branch()
    {
        return $this->belongsTo(CompanyBranch::class, "branch_id");
    }
    public function temperatureSensors()
    {
        return $this->hasMany(DeviceTemperatureSensors::class, "device_id", "id");
    }

    public function deviceSensorLogs()
    {
        return $this->belongsTo(DeviceSensorLogs::class, "device_sensor_logs_id");
    }


    protected $casts = [
        'created_at' => 'datetime:d-M-y',
    ];

    public function scopeExcludeMobile($query)
    {
        return $query->where('name', 'not like', '%Mobile%')->where('name', 'not like', '%Manual%');
    }
}
