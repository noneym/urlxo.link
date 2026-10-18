@props(['title' => null, 'description' => null, 'noindex' => false])
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $title ? $title.' | urlxo' : 'urlxo | Short links with real analytics and an API' }}</title>
<meta name="description" content="{{ $description ?? 'Shorten links on urlxo.link, track every click by country, device and referrer, and automate it all with a simple REST API.' }}">
@if ($noindex)<meta name="robots" content="noindex">@endif
<meta name="theme-color" content="#f3f4f2" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#0d0f10" media="(prefers-color-scheme: dark)">
<meta property="og:title" content="{{ $title ?? 'urlxo' }}">
<meta property="og:description" content="{{ $description ?? 'Short links with real analytics and an API.' }}">
<meta property="og:type" content="website">
<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
@vite(['resources/css/app.css', 'resources/js/app.js'])
