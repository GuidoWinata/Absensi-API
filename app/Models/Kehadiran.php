<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kehadiran extends Model
{
    use HasFactory;
    protected $fillable = ['tanggal', 'waktu_datang', 'waktu_pulang', 'keterangan', 'siswa_id'];
    protected $table = 'kehadiran';
    public $timestamps = false;
    protected $with = ['siswa'];

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }
}
