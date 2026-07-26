<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
</head>
<body>
    <h2>{{ $title }}</h2>
    <p>{!! nl2br(e($body)) !!}</p>
</body>
</html>
