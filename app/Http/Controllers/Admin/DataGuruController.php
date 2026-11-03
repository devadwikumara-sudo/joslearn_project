<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GuruBiodata;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DataGuruController extends Controller
{
    public function index(): View
    {
        $guru = GuruBiodata::all();
        $mapelList = [];

        return view('admin.data-guru', compact('guru', 'mapelList'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:150'],
        ]);

        GuruBiodata::create([
            'nama'               => $request->nama_lengkap,
            'nip'                => $request->nip,
            'tempat_lahir'       => $request->tempat_lahir,
            'tanggal_lahir'      => $request->tanggal_lahir,
            'nomor_hp'           => $request->no_hp,
            'email'              => $request->email,
            'jenis_kelamin'      => $request->jenis_kelamin,
            'status_kepegawaian' => $request->golongan,
        ]);

        return back()->with('success', 'Data guru berhasil disimpan.');
    }

    public function edit(int $id): View
    {
        $guru = GuruBiodata::findOrFail($id);
        return view('admin.data-guru', ['guru' => collect([$guru]), 'mapelList' => []]);
    }

    public function update(Request $request, int $id)
    {
        $guru = GuruBiodata::findOrFail($id);
        $guru->update($request->only(['nama', 'nip', 'nomor_hp', 'email']));
        return back()->with('success', 'Data guru berhasil diperbarui.');
    }
}

