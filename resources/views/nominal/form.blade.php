@extends('layouts.app')

@section('title', 'Data nominal')

@section('contents')
    <form action="{{ isset($nominal) ? route('nominal.tambah.update', $nominal->id) : route('nominal.tambah.simpan') }}"
        method="post">
        @csrf
        <div class="row">
            <div class="col-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            {{ isset($jawdal) ? 'Form Sunting nominal' : 'Form Tambah nominal' }}</h6>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="nominal"> Harga</label>
                            <input type="text" class="form-control" id="nominal" name="nominal"
                                value="{{ isset($nominal) ? $nominal->tarif : '' }}">
                        </div>
                        {{-- <div class="form-group">
                            <label for="harga"> Harga</label>
                            <input type="text" class="form-control" id="harga" name="harga" value="{{ isset($nominal) ? $nominal->harga : ''}}">
                        </div>
                        <div class="form-group">
                            <label for="status"> Status</label>
                            <input type="text" class="form-control" id="status" name="status" value="{{ isset($nominal) ? $nominal->status : ''}}">
                        </div>

                        <div class="form-group">
                            <label for="waktu"> Waktu</label>
                            <input type="text" class="form-control" id="waktu" name="waktu" value="{{ isset($nominal) ? $nominal->waktu : ''}}">
                        </div> --}}
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </div>

            </div>

        </div>
    </form>

@endsection
