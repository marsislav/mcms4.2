@extends('errors.layout')
@section('code', '404')
@section('title', t('error.404.title', 'Page not found'))
@section('message', t('error.404.text', 'The address does not exist or has been moved.'))
