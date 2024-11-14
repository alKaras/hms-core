<!DOCTYPE html>
<html>

<head>
    <title>Талон {{$details['id']}}</title>
    <style>
        @font-face {
            font-family: 'DejaVuSans';
            src: url('{{ storage_path('fonts/DejaVuSans.ttf') }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        body {
            font-family: 'DejaVuSans';
            margin: 0;
            padding: 20px;
        }

        .ticket-header {
            text-align: center;
            border-bottom: 2px solid dodgerblue;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        h1 {
            font-size: 24px;
            color: #333;
        }

        h2 {
            font-size: 18px;
            margin: 5px 0;
            color: #555;
        }

        .ticket-info {
            margin-bottom: 20px;
            padding: 10px;
            border: 1px solid #ddd;
            background-color: #f9f9f9;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table,
        th,
        td {
            border: 1px solid #ddd;
        }

        th,
        td {
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: dodgerblue;
            color: white;
        }

        td {
            font-size: 14px;
            color: #333;
        }

        .note {
            font-size: 12px;
            color: #777;
            text-align: center;
        }
    </style>
</head>

<body>

    <div class="ticket-header">
        <h1>Талон на відвідування послуги</h1>
    </div>

    <div class="ticket-info">
        <h2>Загальна інформація</h2>
        <p><strong>Послуга: </strong> {{ $details['service']['name'] }}</p>
        <p><strong>Орієнтований час початку: </strong> {{ date('d/m/Y H:i', strtotime($details['start_time'])) }}</p>
        <p><strong>Лікарня: </strong>
            {{ $details['hospital']['title'] ?? null }}
            {{$details['hospital']['address'] ?? null}}
        </p>
        <p><strong>Телефон лікарні: </strong> {{ $details['hospital']['public_phone'] ?? null }}</p>
        </p>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Лікар</th>
                <th>Послуга</th>
                <th>Відділ</th>
                <th>Ціна</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $details['service']['id'] }}</td>
                <td>{{ $details['doctor']['name'] }} {{ $details['doctor']['surname'] }}</td>
                <td>{{ $details['service']['name'] }}</td>
                <td>{{ $details['service']['department'] }}</td>
                <td>{{ $details['price']}}</td>
            </tr>
        </tbody>
    </table>

    <p class="note">
        Будь ласка, приходьте за 10 хвилин до призначеного часу і візьміть з собою всі необхідні документи.
    </p>

</body>

</html>