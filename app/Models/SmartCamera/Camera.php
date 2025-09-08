<?php

namespace App\Models\SmartCamera;

use Illuminate\Database\Eloquent\Model;

class Camera extends Model
{
    protected $table = 'cameras_fake'; // nếu không có DB thì đặt bảng giả
    public $timestamps = false;

    protected $fillable = [
        'id',
        'name',
        'ip_address',
        'port',
        'username',
        'password',
        'stream_url',
        'status',
        'type',
        'location',
        'description',
        'is_active',
        'recording_enabled',
        'motion_detection_enabled',
        'face_recognition_enabled',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'id' => 'integer',
        'port' => 'integer',
        'status' => 'integer',
        'type' => 'integer',
        'is_active' => 'boolean',
        'recording_enabled' => 'boolean',
        'motion_detection_enabled' => 'boolean',
        'face_recognition_enabled' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
