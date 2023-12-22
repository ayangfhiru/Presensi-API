<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Email</title>
    <style>
        .content {
            display: flex;
            align-items: center;
            flex-direction: column;
        }
    </style>
</head>

<body>
    <div class="content">
        <div class="detail">
            <h3>Hai {{ $mailData['username'] }}</h3>
            <p>Anda terdaftar sebagai {{ $mailData['role'] }}.</p>
            <p>{{ $mailData['division'] ?? 'di bagian divisi' }} {{ $mailData['division'] }}</p>
            <p>Berikut merupakan akses untuk melakukan login presensi di PT Inastek</p>
            <p>Email {{ $mailData['email'] }}</p>
            <p>Password {{ $mailData['password'] }}</p>
            <p>Harap setelah login untuk merubah password!</p>
        </div>
    </div>
</body>

</html>