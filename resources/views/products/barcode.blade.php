<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barcode {{ $product->code }}</title>
    <style>
        @page {
            size: auto;
            margin: 5mm;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 16px;
        }
        .label {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            width: 320px;
            max-width: 100%;
            padding: 20px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,.08);
        }
        .brand {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 4px;
        }
        .name {
            font-size: 17px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.3;
            margin-bottom: 14px;
        }
        .barcode {
            display: flex;
            align-items: stretch;
            justify-content: center;
            height: 74px;
            gap: 0;
            overflow: hidden;
            margin-bottom: 6px;
        }
        .barcode .bar { display: block; height: 100%; }
        .code {
            font-family: 'Consolas', 'JetBrains Mono', monospace;
            font-size: 13px;
            letter-spacing: .18em;
            color: #0f172a;
            margin-bottom: 6px;
        }
        .price {
            font-size: 16px;
            font-weight: 800;
            color: #1d4ed8;
        }
        .product-no {
            font-size: 11px;
            color: #94a3b8;
            margin-bottom: 8px;
        }
        .toolbar { margin-top: 16px; text-align: center; }
        .btn {
            display: inline-block;
            padding: 8px 18px;
            background: #1d4ed8;
            color: #fff;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }
        @media print {
            body { background: #fff; padding: 0; min-height: 0; }
            .toolbar { display: none; }
            .label { box-shadow: none; border: none; border-radius: 0; width: 100%; }
        }
    </style>
</head>
<body>
    @php
        $code = $product->barcode ?: $product->code ?: $product->product_no ?: (string) $product->id;
        $chars = str_split((string) $code);
    @endphp
    <div>
        <div class="label">
            <div class="brand">{{ $appSettings['name'] ?? config('app.name') }}</div>
            <div class="name">{{ $product->name }}</div>
            <div class="barcode">
                @foreach ($chars as $i => $c)
                    @php
                        $w = (ord($c) % 4) + 1;
                        $black = (ord($c) + $i) % 2 === 0;
                    @endphp
                    <span class="bar" style="width:{{ $w }}px; background:{{ $black ? '#0f172a' : 'transparent' }}"></span>
                @endforeach
            </div>
            <div class="code">{{ $code }}</div>
            <div class="price">@money($product->price)</div>
            <div class="product-no">{{ $product->product_no }}</div>
        </div>
        <div class="toolbar">
            <a href="javascript:window.print()" class="btn">Cetak</a>
            <a href="{{ route('products.show', $product) }}" class="btn" style="background:#64748b;">Kembali</a>
        </div>
    </div>
    <script>
        window.addEventListener('load', function () {
            setTimeout(function () { window.print(); }, 400);
        });
    </script>
</body>
</html>
