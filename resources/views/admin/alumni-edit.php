@extends('layouts.app')

@section('title', 'Edit Biodata Alumni — JosBook')

@section('content')
<h2>Edit Biodata Alumni</h2>
<p class="sub">Perbarui biodata lengkap dan dokumen tanpa mengubah riwayat kelas alumni.</p>

@include('admin._form-pendataan', [
    'formData' => $formData,
    'formCloseUrl' => $formCloseUrl,
])
@endsection
