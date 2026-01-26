@extends('layouts.ege')

@section('content')
    @foreach($blocks as $block)
        @include('ege.partials.block', [
            'block' => $block,
            'topicId' => $topicId,
            'topicMeta' => $topicMeta,
            'color' => 'purple',
        ])
    @endforeach
@endsection
