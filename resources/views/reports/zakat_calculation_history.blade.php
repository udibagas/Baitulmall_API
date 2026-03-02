<!DOCTYPE html>
<html>
<head>
    <title>Riwayat Perhitungan Zakat</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2, .header h3 { margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .footer { margin-top: 30px; text-align: right; font-size: 10px; color: #777; }
        .badge { padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 10px; }
        .badge-success { background-color: #d4edda; color: #155724; }
        .badge-warning { background-color: #fff3cd; color: #856404; }
        .badge-secondary { background-color: #e2e3e5; color: #383d41; }
    </style>
</head>
<body>
    <div class="header">
        <h2>BAITULMALL MASJID</h2>
        <h3>Laporan Riwayat Perhitungan Zakat</h3>
        <p>Muzaki: <strong>{{ $muzaki->nama }}</strong> (RT {{ $muzaki->rt->kode ?? '-' }})</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Jenis Zakat</th>
                <th>Total Harta / Income</th>
                <th>Nisab (Saat Itu)</th>
                <th>Status</th>
                <th>Nominal Zakat</th>
            </tr>
        </thead>
        <tbody>
            @foreach($history as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ \Carbon\Carbon::parse($item->calculation_date)->translatedFormat('d F Y') }}</td>
                <td>{{ $item->zakat_type }}</td>
                <td>Rp {{ number_format($item->total_assets, 0, ',', '.') }}</td>
                <td>Rp {{ number_format($item->nisab_threshold, 0, ',', '.') }}</td>
                <td>
                    @if($item->is_payable && $item->haul_met)
                        <span class="badge badge-success">WAJIB</span>
                    @elseif($item->is_payable && !$item->haul_met)
                        <span class="badge badge-warning">BELUM HAUL</span>
                    @else
                        <span class="badge badge-secondary">TIDAK WAJIB</span>
                    @endif
                </td>
                <td style="font-weight: bold;">
                    @if($item->is_payable && $item->haul_met)
                        Rp {{ number_format($item->calculated_amount, 0, ',', '.') }}
                    @else
                        -
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Dicetak pada: {{ now()->format('d-m-Y H:i') }}
    </div>
</body>
</html>
