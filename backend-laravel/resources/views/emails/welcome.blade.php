@extends('emails.layout')

@section('content')
    <p>Halo <strong>{{ $full_name }}</strong>,</p>
    <p>Selamat datang di <strong>A2U Bank Digital</strong>! Akun Anda telah berhasil diaktifkan.</p>

    <p>Anda sekarang dapat menikmati berbagai layanan perbankan digital kami:</p>
    <ul>
        <li>Transfer antar rekening</li>
        <li>Pembayaran tagihan</li>
        <li>Pengajuan pinjaman</li>
        <li>Deposito online</li>
        <li>Dan masih banyak lagi</li>
    </ul>

    <p style="text-align: center; margin: 30px 0;">
        <a href="{{ config('app.url') }}" class="button">Mulai Sekarang</a>
    </p>

    <p>Jika Anda memiliki pertanyaan, jangan ragu untuk menghubungi tim customer service kami.</p>
    <br>
    <p>Hormat kami,</p>
    <p><strong>Tim A2U Bank Digital</strong></p>
@endsection
