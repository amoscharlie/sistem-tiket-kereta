@extends('layouts.app')

@section('title', 'Data Nominal')

@section('contents')

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Nominal</h6>
        </div>
        <div class="card-body">
            <a href="{{ route('nominal.tambah') }}" class="btn btn-primary mb-3">Tambah Nominal</a>
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Harga</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @php($no = 1)
                        @foreach ($nominal as $row)
                            <tr>
                                <th>{{ $no++ }}</th>
                                <td>{{ $row->nominal->tarif }}</td>
                                <td>
                                    <a href="{{ route('nominal.sunting', $row->id) }}" class="btn btn-warning">Sunting</a>
                                    <a href="{{ route('nominal.hapus', $row->id) }}" class="btn btn-danger">Hapus</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
