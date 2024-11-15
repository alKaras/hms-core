<!DOCTYPE html>
<html>

<head>
    <title>Висновок послуги {{$details['service']['name']}}</title>
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

        h3 {
            font-size: 16px;
            margin: 5px 0;
            color: black;
            font-weight: bold;
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

        .main-block {
            margin-top: 15px;
            margin-bottom: 15px;

        }
    </style>
</head>

<body>

    <div class="ticket-header">
        <h1>Медичний висновок</h1>
    </div>

    <div class="ticket-info">
        <h2>Загальна інформація</h2>
        <p>
            <strong>Послуга: </strong>
            {{ $details['service']['name'] }}
        </p>
        <p>
            <strong>Орієнтований час початку: </strong>
            {{ date('d/m/Y H:i', strtotime($details['service']['start_time'])) }}
        </p>
        <p>
            <strong>Лікар: </strong>
            {{ $details['doctor']['name']}} {{ $details['doctor']['surname'] }}
        </p>
        <p style="margin-bottom: 15px">
            <strong>Пошта лікаря: </strong>
            {{ $details['doctor']['email'] ?? null }}
        </p>
        <h3>Пацієнт</h3>
        <p>
            <strong>Ім'я:</strong>
            {{ $details['patient']['name']}} {{$details['patient']['surname']}}
        </p>
        <p>
            <strong>Пошта:</strong>
            {{ $details['patient']['email']}}
        </p>
    </div>

    <div class="main-block">
        <h3>Висновок:</h3>
        <p>
            {{$details['summary']}}
        </p>

        <h3>Рекомендації:</h3>
        <p>
            {{$details['recommendations']}}
        </p>

        <h3>Замітки:</h3>
        <p>
            {{$details['notes']}}
        </p>
    </div>

</body>

</html>