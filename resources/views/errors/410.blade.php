@extends('errors.layout')

@section('title', __('Link Expired'))
@section('code', '410')
@section('message')
    {{ $exception->getMessage() ?: __('Sorry, this link has expired and is no longer available.') }}
@endsection
