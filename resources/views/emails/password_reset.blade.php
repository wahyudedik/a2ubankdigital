@extends('emails.layout')

@section('content')
    <p>Halo <strong>{{ $full_name }}</strong>,</p>
    <p>Kami menerima permintaan untuk mereset password akun Anda. Gunakan kode verifikasi di bawah ini untuk melanjutkan
        proses reset password:</p>

    <div class="otp-code">
        <h2>{{ $otp_code }}</h2>
    </div>

    <p>Kode ini akan kedaluwarsa dalam <strong>10 menit</strong>. Jika Anda tidak meminta reset password, silakan abaikan
        email ini dan pastikan akun Anda aman.</p>
    <br>
    <p>Hormat kami,</p>
    <p><strong>Tim A2U Bank Digital</strong></p>
@endsection
