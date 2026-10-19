@extends('errors.layout')
@section('code', '404')
@section('title', t('error.404.title', __('Страницата не е намерена')))
@section('message', t('error.404.text', __('Адресът не съществува или е преместен.')))
