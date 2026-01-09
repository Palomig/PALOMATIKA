@extends('layouts.topic')

@section('content')
    @foreach($blocks as $block)
        @include('tasks.block', [
            'block' => $block,
            'topicId' => $topicId,
            'topicMeta' => $topicMeta,
            'color' => $topicMeta['color'] ?? 'blue',
        ])
    @endforeach
@endsection
