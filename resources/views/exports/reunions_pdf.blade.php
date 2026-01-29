<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11px; color: #333; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #4f46e5; padding-bottom: 10px; }
        .header h1 { color: #4f46e5; margin: 0; font-size: 24px; }
        .header p { color: #666; margin: 5px 0 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; table-layout: fixed; }
        th, td { border: 1px solid #e5e7eb; padding: 10px 8px; text-align: left; word-wrap: break-word; }
        th { background-color: #f9fafb; color: #374151; font-weight: bold; text-transform: uppercase; font-size: 10px; width: auto; }
        tr:nth-child(even) { background-color: #fcfcfc; }
        .status { padding: 2px 6px; border-radius: 4px; font-size: 9px; font-weight: bold; }
        .status-planifiee { background-color: #dcfce7; color: #166534; }
        .status-en_cours { background-color: #fef9c3; color: #854d0e; }
        .status-terminee { background-color: #dbeafe; color: #1e40af; }
        .status-annulee { background-color: #fee2e2; color: #991b1b; }
        .footer { position: fixed; bottom: -10px; left: 0; right: 0; text-align: center; font-size: 9px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Liste des Réunions</h1>
        <p>{{ $title }}</p>
        <p style="font-size: 9px;">Généré le {{ $date }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 25%;">Objet</th>
                <th style="width: 15%;">Début</th>
                <th style="width: 15%;">Fin</th>
                <th style="width: 20%;">Lieu / Type</th>
                <th style="width: 15%;">Statut</th>
                <th style="width: 10%;">Participants</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reunions as $reunion)
            <tr>
                <td>
                    <div style="font-weight: bold;">{{ $reunion->objet }}</div>
                    @if($reunion->description)
                    <div style="font-size: 9px; color: #666; margin-top: 4px;">{{ Str::limit($reunion->description, 100) }}</div>
                    @endif
                </td>
                <td>{{ $reunion->date_debut->format('d/m/Y') }}<br>{{ $reunion->date_debut->format('H:i') }}</td>
                <td>{{ $reunion->date_fin->format('d/m/Y') }}<br>{{ $reunion->date_fin->format('H:i') }}</td>
                <td>
                    {{ $reunion->lieu ?: 'N/A' }}<br>
                    <span style="color: #666; font-style: italic;">{{ ucfirst($reunion->type) }}</span>
                </td>
                <td>
                    <span class="status status-{{ $reunion->statut }}">
                        {{ strtoupper(str_replace('_', ' ', $reunion->statut)) }}
                    </span>
                </td>
                <td>{{ $reunion->invitations->count() }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        © {{ date('Y') }} - Système de Gestion de Réunions
    </div>
</body>
</html>
