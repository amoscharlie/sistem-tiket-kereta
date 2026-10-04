<?php

namespace App\Http\Controllers;

use App\Models\pesan;
use App\Models\nominal;
use Illuminate\Http\Request;


/**
 * Summary of pesanController
 */
class pesanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $pesan = pesan::get();
        return view('pesan.index', ['pesan'=>$pesan]);
    }

    public function tambah() {
        $nominal = nominal::get();

        return view ('pesan.form',['nominal'=> $nominal]);
    }


    public function simpan(Request $request)
    {
        $data = [
            'nama' => $request->nama,
            'id_nominal' =>$request->id_nominal,
            'tanggal' => $request->tanggal,
            'status' => $request ->status,

        ];

        pesan::create($data);

        return redirect()->route('pesan');
    }


    public function sunting ($id)
    {
        $pesan =pesan::find($id)->first();
        $nominal = nominal::get();

        return view('pesan.form', ['pesan' => $pesan, 'nominal' =>$nominal]);
    }


    public function update ($id, Request $request)
    {
        $data = [
            'nama' => $request->nama,
            'id_nominal' =>$request->id_nominal,
            'tanggal' => $request->tanggal,
            'status' => $request ->status,

        ];
        pesan::find($id)->update($data);

        return redirect()->route('pesan');
    }

    public function hapus($id)
    {
        pesan::find($id)->delete();

        return redirect()->route('pesan');
    }
}