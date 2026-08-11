@extends('layouts.topic', ['bank' => 'ege'])

@section('content')
    @foreach($blocks as $block)
        @include('tasks.block', [
            'block' => $block,
            'topicId' => $topicId,
            'topicMeta' => $topicMeta,
            'color' => $topicMeta['color'] ?? 'purple',
            'bank' => 'ege',
        ])
    @endforeach
@endsection
