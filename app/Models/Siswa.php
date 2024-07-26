<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Siswa extends Model
{
    use HasFactory;
    protected $table = 'siswa';
    protected $fillable = ['nisn', 'nama', 'kelas_id', 'tanggal_lahir'];

    protected $with = ['kelas'];
    public $timestamps = false;

    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }
}
