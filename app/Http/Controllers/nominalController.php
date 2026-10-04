<?php

namespace App\Http\Controllers;

use App\Models\nominal;
use Illuminate\Http\Request;

class nominalController extends Controller
{
public function index(){
    $nominal = nominal::get();

    return view('nominal.index', ['nominal' => $nominal]);
}
public function tambah() {
    return view ('nominal.form');
}


public function simpan(Request $request)
{
    $data = [
        'nominal' => $request->nominal,

    ];

    nominal::create($data);

    return redirect()->route('nominal');
}


public function sunting ($id)
{
    $nominal =nominal::find($id)->first();

    return view('nominal.form', ['nominal' => $nominal]);
}


public function update ($id, Request $request)
{
    $data = [
        'nominal' => $request->nominal,

    ];
    nominal::find($id)->update($data);

    return redirect()->route('nominal');
}

public function hapus($id)
{
    nominal::find($id)->delete();

    return redirect()->route('nominal');
}
}