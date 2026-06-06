<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TrailScope</title>
    @foreach ($trailStyles as $trailStyle)
        <link rel="stylesheet" href="{{ route('trail.assets', ['asset' => $trailStyle]) }}">
    @endforeach
    <script type="module" src="{{ route('trail.assets', ['asset' => $trailScript]) }}"></script>
    @inertiaHead
</head>
<body>
    @inertia
</body>
</html>
