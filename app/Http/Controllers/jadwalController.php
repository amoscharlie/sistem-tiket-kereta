<?php

namespace App\Http\Controllers;

use App\Models\jadwal;
use Illuminate\Http\Request;

class jadwalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $jadwal = jadwal::get();
        return view('jadwal.index', ['jadwal'=>$jadwal]);
    }

    public function tambah() {
        return view ('jadwal.form');
    }

    public function simpan(Request $request)
    {
        $data = [
            'tujuan' => $request->tujuan,
            'harga' => $request->harga,
            'status' => $request ->status,
            'waktu' => $request -> waktu,
        ];

        jadwal::create($data);

        return redirect()->route('jadwal');
    }

    public function sunting ($id)
    {
        $jadwal =jadwal::find($id)->first();

        return view('jadwal.form', ['jadwal' => $jadwal]);
    }

    public function update ($id, Request $request)
    {
        $data = [
            'tujuan' => $request->tujuan,
            'harga' => $request->harga,
            'status' => $request ->status,
            'waktu' => $request -> waktu,
        ];
        jadwal::find($id)->update($data);

        return redirect()->route('jadwal');
    }

    public function hapus($id)
    {
        jadwal::find($id)->delete();

        return redirect()->route('jadwal');
    }

}