@extends('layouts.app')

@section('title', 'Data pesan')

@section('contents')
    <form action="{{ isset($pesan) ? route('pesan.tambah.update', $pesan->id) : route('pesan.tambah.simpan') }}"
        method="post">
        @csrf
        <div class="row">
            <div class="col-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            {{ isset($pesan) ? 'Form Sunting pesan' : 'Form Tambah pesan' }}</h6>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="nama"> Nama</label>
                            <input type="text" class="form-control" id="nama" name="nama"
                                value="{{ isset($pesan) ? $pesan->nama : '' }}">
                        </div>
                        <div class="form-group">
                            <label for="id_nominal"> Harga</label>
                            <select name="id_nominal" id="id_nominal" class="custom-select">
                                @foreach ($nominal as $row)
                                    <option value="{{ $row->id }}">{{ $row->tarif }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="status"> Status</label>
                            <input type="text" class="form-control" id="status" name="status"
                                value="{{ isset($pesan) ? $pesan->status : '' }}">
                        </div>

                        <div class="form-group">
                            <label for="tanggal"> tanggal</label>
                            <input type="text" class="form-control" id="tanggal" name="tanggal"
                                value="{{ isset($pesan) ? $pesan->tanggal : '' }}">
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </div>

            </div>

        </div>
    </form>

@endsection
