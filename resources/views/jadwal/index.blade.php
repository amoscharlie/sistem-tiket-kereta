@extends('layouts.app')

@section('title', 'Data Jadwal')

@section('contents')

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Jadwal Kereta Api</h6>
        </div>
        <div class="card-body">
            <a href="{{ route('jadwal.tambah') }}" class="btn btn-primary mb-3">Tambah Jadwal</a>
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tujuan</th>
                            <th>Harga</th>
                            <th>Status</th>
                            <th>Waktu</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @php($no = 1)
                        @foreach ($jadwal as $row)
                            <tr>
                                <th>{{ $no++ }}</th>
                                <td>{{ $row->tujuan }}</td>
                                <td>{{ $row->harga }}</td>
                                <td>{{ $row->status }}</td>
                                <td>{{ $row->waktu }}</td>
                                <td>
                                    <a href="{{ route('jadwal.sunting', $row->id) }}" class="btn btn-warning">Sunting</a>
                                    <a href="{{ route('jadwal.hapus', $row->id) }}" class="btn btn-danger">Hapus</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
