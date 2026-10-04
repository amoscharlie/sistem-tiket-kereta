@extends('layouts.app')

@section('title', 'Data pesan')

@section('contents')

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">pesan Kereta Api</h6>
        </div>
        <div class="card-body">
            <a href="{{ route('pesan.tambah') }}" class="btn btn-primary mb-3">Tambah pesan</a>
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama</th>
                            <th>Nominal</th>
                            <th>Status</th>
                            <th>Waktu</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @php($no = 1)
                        @foreach ($pesan as $row)
                            <tr>
                                <th>{{ $no++ }}</th>
                                <td>{{ $row->nama }}</td>
                                <td>{{ $row->nominal->tarif }}</td>
                                <td>{{ $row->status }}</td>
                                <td>{{ $row->tanggal }}</td>
                                <td>
                                    <a href="{{ route('pesan.sunting', $row->id) }}" class="btn btn-warning">Sunting</a>
                                    <a href="{{ route('pesan.hapus', $row->id) }}" class="btn btn-danger">Hapus</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
