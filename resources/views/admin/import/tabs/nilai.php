@include('admin.import._form')

@if($isAdmin)
    @include('admin.import._batch-list', [
        'type' => $type,
        'indexRoute' => $indexRoute,
        'batches' => $batches,
        'tahunAjaranList' => $tahunAjaranList,
        'tahunAjaranTerpilihId' => $tahunAjaranTerpilihId,
    ])
@else
    @include('admin.import._teacher-history', ['type' => $type])
@endif
