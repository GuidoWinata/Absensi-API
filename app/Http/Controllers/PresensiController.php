<?php

namespace App\Http\Controllers;

use App\Models\Dispen;
use App\Models\Image;
use App\Models\Izin;
use App\Models\Kehadiran;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PresensiController extends Controller
{
    public function absen(Request $request)
    {
        $siswa_id = Auth::user()->siswa_id;
        $tanggal = Carbon::now()->format('d-m-Y');
        $currentTime = Carbon::now('Asia/Jakarta');

        // Mengonversi tanggal ke format Y-m-d untuk disimpan ke database
        $tanggalFormatted = Carbon::createFromFormat('d-m-Y', $tanggal)->format('Y-m-d');

        // Cek apakah siswa sudah absen pada hari itu
        $absen = Kehadiran::where('siswa_id', $siswa_id)
            ->where('tanggal', $tanggalFormatted)
            ->first();

        if ($absen) {
            // Jika sudah absen dan ingin absen pulang
            if ($absen->waktu_datang !== null) {
                if ($absen->waktu_pulang) {
                    return response()->json(['message' => 'Anda sudah absen pulang hari ini.'], 422);
                } else {
                    // Cek jika waktu sekarang sudah waktunya pulang
                    $waktu_pulang = Carbon::parse('16:00:00', 'Asia/Jakarta'); // contoh waktu pulang jam 16:00
                    if ($currentTime->greaterThanOrEqualTo($waktu_pulang)) {
                        $absen->update(['waktu_pulang' => $currentTime]);
                        return response()->json(['message' => 'Berhasil Absen Pulang', 'data' => $absen], 201);
                    } else {
                        return response()->json(['message' => 'Belum waktunya pulang.'], 422);
                    }
                }
            } else {
                return response()->json(['message' => 'Anda sudah absen datang hari ini.'], 422);
            }
        } else {
            // Jika belum absen, buat data absen baru
            $absen = Kehadiran::create([
                'siswa_id' => $siswa_id,
                'tanggal' => $tanggalFormatted,
                'keterangan' => 'hadir',
                'waktu_datang' => $currentTime,
            ]);
            return response()->json(['message' => 'Berhasil Absen Datang', 'data' => $absen], 200);
        }
    }



    public function Izin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'deskripsi' => 'required',
            'keterangan' => 'required',
            'siswa_id' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'invalid field', 'error' => $validator->errors()], 422);
        }

        $image = $request->file('image');
        $fileName = time() . '_' . $image->getClientOriginalName();
        $filePath = $image->storeAs('uploads', $fileName, 'public');
        $tanggal = Carbon::now();
        $siswa_id = $request->siswa_id;

        $imageUpload = Image::create([
            'file_name' => $fileName,
            'file_path' => '/storage/app/public/' . $filePath
        ]);

        $izin = Izin::create([
            'siswa_id' => $siswa_id,
            'image_id' => $imageUpload->id,
            'tanggal' => $tanggal,
            'keterangan' => $request->keterangan,
            'deskripsi' => $request->deskripsi,
        ]);

        $kehadiran = Kehadiran::create([
            'siswa_id' => $siswa_id,
            'tanggal' => $tanggal,
            'keterangan' => 'izin',
            'waktu_datang' => null,
            'waktu_pulang' => null,
        ]);

        return response()->json(['message' => 'Siswa Berhasil Izin', 'data' => $izin], 200);
    }

    public function accDispen($id)
    {
        $approve = Dispen::where('id', $id)->where('keterangan', 'pending')->first();
        if ($approve) {
            $approve->update(['keterangan' => 'approve']);
            return response()->json(['message' => 'Berhasil approve dispen siswa ' . $approve->siswa->nama], 200);
        } else {
            return response()->json(['message' => 'Data tidak ditemukan atau sudah diapprove'], 404);
        }
    }

    public function reqDispen(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'deskripsi' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'invalid field', 'error' => $validator->errors()], 422);
        }

        $image = $request->file('image');
        $fileName = time() . '_' . $image->getClientOriginalName();
        $filePath = $image->storeAs('uploads', $fileName, 'public');
        $tanggal = Carbon::now();
        $siswa = Auth::user()->siswa_id;

        $imageUpload = Image::create([
            'file_name' => $fileName,
            'file_path' => '/storage/app/public/' . $filePath
        ]);

        $request = Dispen::create([
            'siswa_id' => $siswa,
            'image_id' => $imageUpload->id,
            'deskripsi' => $request->deskripsi,
            'tanggal' => $tanggal,
        ]);

        return response()->json(['message' => 'Berhasil request dispen tunggu approval dari wali kelas'], 200);
    }
}
