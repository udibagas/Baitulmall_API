<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Kwitansi Digital - Baitulmal</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333;
            font-size: 14px;
            line-height: 1.6;
            margin: 0;
            padding: 40px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #1a1a1a;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .header p {
            margin: 5px 0 0;
            color: #666;
            font-size: 12px;
        }
        .receipt-info {
            width: 100%;
            margin-bottom: 40px;
        }
        .receipt-info td {
            vertical-align: top;
        }
        .receipt-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 20px;
            text-align: center;
            color: #1a1a1a;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
        }
        .details-table th, .details-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }
        .details-table th {
            color: #71717a;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .details-table td {
            font-weight: bold;
        }
        .amount-section {
            background: #f8f8f8;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 40px;
        }
        .amount-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        .total-amount {
            font-size: 24px;
            font-weight: 900;
            color: #1a1a1a;
        }
        .footer {
            margin-top: 60px;
            width: 100%;
        }
        .footer td {
            width: 50%;
            text-align: center;
        }
        .signature-space {
            height: 80px;
        }
        .stamp {
            color: #10b981;
            border: 2px solid #10b981;
            padding: 5px 10px;
            display: inline-block;
            font-weight: bold;
            text-transform: uppercase;
            transform: rotate(-5deg);
            opacity: 0.7;
            font-size: 10px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Baitulmal Digital</h1>
        <p>Lembaga Amil Zakat & Infaq Masjid Baitulmal</p>
        <p>Jl. Raya Kandri, Kec. Gunung Pati, Kota Semarang</p>
    </div>

    <div class="receipt-title">BUKTI PENERIMAAN {{ strtoupper($type_label) }}</div>

    <table class="receipt-info">
        <tr>
            <td>
                <strong>No. Kwitansi:</strong><br>
                {{ $receipt_no }}
            </td>
            <td style="text-align: right;">
                <strong>Tanggal:</strong><br>
                {{ date('d M Y', strtotime($date)) }}
            </td>
        </tr>
    </table>

    <table class="details-table">
        <tr>
            <th>Donatur / Muzaki</th>
            <td>{{ $donor_name }}</td>
        </tr>
        <tr>
            <th>Kategori</th>
            <td>{{ $category }}</td>
        </tr>
        <tr>
            <th>Keterangan</th>
            <td>{{ $note }}</td>
        </tr>
        @if(isset($jiwa) && $jiwa > 1)
        <tr>
            <th>Jumlah Jiwa</th>
            <td>{{ $jiwa }} Orang</td>
        </tr>
        @endif
    </table>

    <div class="amount-section">
        <div style="color: #71717a; font-size: 11px; text-transform: uppercase; margin-bottom: 10px;">Total Kontribusi</div>
        @if($amount_money > 0)
        <div class="total-amount">Rp {{ number_format($amount_money, 0, ',', '.') }}</div>
        @endif
        @if($amount_rice > 0)
        <div class="total-amount">{{ number_format($amount_rice, 1, ',', '.') }} Kg Beras</div>
        @endif
    </div>

    <p style="font-size: 12px; color: #666; italic">"Semoga Allah memberikan pahala atas apa yang telah engkau berikan, menjadikannya pembersih bagimu, dan memberkati hartamu yang masih tersisa."</p>

    <table class="footer">
        <tr>
            <td>
                <p>Petugas Amil,</p>
                <div class="signature-space"></div>
                <p><strong>Baitulmal Admin</strong></p>
            </td>
            <td>
                <div class="stamp">TERVERIFIKASI DIGITAL</div>
                <p>Dicetak pada: {{ date('d/m/Y H:i') }}</p>
                <div style="font-size: 10px; color: #999; margin-top: 20px;">
                    Simpan bukti ini sebagai referensi resmi donasi Anda.
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
