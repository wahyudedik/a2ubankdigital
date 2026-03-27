@extends('emails.layout')

@section('content')
    <p>Halo <strong>{{ $full_name }}</strong>,</p>
    <p>Terima kasih telah mendaftar di A2U Bank Digital. Untuk melanjutkan, silakan gunakan kode verifikasi (OTP) di bawah
        ini:</p>

    <div class="otp-code">
        <h2>{{ $otp_code }}</h2>
    </div>

    <p>Kode ini akan kedaluwarsa dalam <strong>10 menit</strong>. Mohon untuk tidak membagikan kode ini kepada siapa pun
        demi keamanan akun Anda.</p>
    <p>Jika Anda tidak merasa melakukan pendaftaran ini, silakan abaikan email ini.</p>
    <br>
    <p>Hormat kami,</p>
    <p><strong>Tim A2U Bank Digital</strong></p>
@endsection
