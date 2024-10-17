<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="secure-cookie" content="{{ config('session.secure_cookie') ? 'true' : 'false' }}">
    <title>Calories 365</title>
    @vite('resources/js/app.js')
    @vite('resources/css/app.css')

    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="/images/icons8-calories-80.png">
</head>
<body>
<div id="app"></div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</body>
</html>
