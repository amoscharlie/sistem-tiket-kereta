@extends('layouts.app')

@section('title', 'Data Jadwal')

@section('contents')
    <form action="{{ isset($jadwal) ?route('jadwal.tambah.update', $jadwal->id): route('jadwal.tambah.simpan') }}" method="post">
@csrf
        <div class="row">
            <div class="col-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">{{ isset($jawdal) ?'Form Sunting Jadwal' : 'Form Tambah Jadwal' }}</h6>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="tujuan"> Tujuan</label>
                            <input type="text" class="form-control" id="tujuan" name="tujuan" value="{{ isset($jadwal) ? $jadwal->tujuan : ''}}">
                        </div>
                        <div class="form-group">
                            <label for="harga"> Harga</label>
                            <input type="text" class="form-control" id="harga" name="harga" value="{{ isset($jadwal) ? $jadwal->harga : ''}}">
                        </div>
                        <div class="form-group">
                            <label for="status"> Status</label>
                            <input type="text" class="form-control" id="status" name="status" value="{{ isset($jadwal) ? $jadwal->status : ''}}">
                        </div>

                        <div class="form-group">
                            <label for="waktu"> Waktu</label>
                            <input type="text" class="form-control" id="waktu" name="waktu" value="{{ isset($jadwal) ? $jadwal->waktu : ''}}">
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
