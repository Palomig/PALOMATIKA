@extends('layouts.topic', ['bank' => $bank ?? 'ege'])

@section('content')
    @foreach($blocks as $block)
        @include('tasks.block', [
            'block' => $block,
            'topicId' => $topicId,
            'topicMeta' => $topicMeta,
            'color' => $topicMeta['color'] ?? 'purple',
            'bank' => $bank ?? 'ege',
        ])
    @endforeach
@endsection
