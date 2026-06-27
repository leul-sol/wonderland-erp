<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        .letterhead { margin-bottom: 12px; border-bottom: 2px solid #0f766e; padding-bottom: 8px; }
        .letterhead img { max-height: 48px; }
        .letterhead h2 { margin: 0; font-size: 18px; color: #0f766e; }
        .letterhead p { margin: 2px 0 0; color: #555; font-size: 10px; }
        h1 { font-size: 16px; margin-bottom: 4px; }
        .meta { color: #555; margin-bottom: 16px; line-height: 1.5; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ccc; padding: 4px 6px; text-align: left; }
        th { background: #f3f3f3; }
    </style>
</head>
<body>
    <div class="letterhead">
        @if(!empty($letterheadDataUri))
            <img src="{{ $letterheadDataUri }}" alt="Wonderland Hotel">
        @else
            <h2>Wonderland Hotel</h2>
            <p>Finance &amp; Business Intelligence</p>
        @endif
    </div>
    <h1>{{ $title }}</h1>
    <div class="meta">
        <div>Generated {{ $generatedAt }}</div>
        @if(!empty($generatedBy))
            <div>Prepared by {{ $generatedBy }}</div>
        @endif
        @if(!empty($periodLabel))
            <div>Period {{ $periodLabel }}</div>
        @endif
        @if(!empty($reportSlug))
            <div>Report {{ $reportSlug }}</div>
        @endif
    </div>
    <table>
        @foreach($rows as $row)
            <tr>
                @foreach($row as $cell)
                    <td>{{ $cell }}</td>
                @endforeach
            </tr>
        @endforeach
    </table>
</body>
</html>
