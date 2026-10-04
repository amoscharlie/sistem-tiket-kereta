<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tiket Kereta API</title>
    <style>
        body { margin: 0; font-family: ui-sans-serif, system-ui, sans-serif; background: #eef2f6; color: #122033; }
        main { max-width: 640px; margin: 48px auto; padding: 0 20px; }
        h1 { font-size: 28px; margin: 0 0 8px; }
        p { color: #5d6b7c; }
        a { color: #d7263d; }
        pre { background: #0e2a47; color: #f4f7fb; padding: 16px 18px; border-radius: 12px; overflow: auto; }
    </style>
</head>
<body>
    <main>
        <h1>Tiket Kereta API</h1>
        <p>API bisa diakses. Situs pemesanan ada di <a href="{{ $data['situs'] }}">{{ $data['situs'] }}</a>.</p>
        <pre>{{ json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
    </main>
</body>
</html>
