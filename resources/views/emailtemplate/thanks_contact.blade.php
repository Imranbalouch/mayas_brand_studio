<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            background-color: #dc3b14;
            color: #fff;
            padding: 15px;
            border-radius: 5px 5px 0 0;
            text-align: center;
        }
        .content {
            padding: 20px;
            color: #333;
        }
        .footer {
            text-align: center;
            padding: 10px;
            font-size: 12px;
            color: #dc3b14;
            border-top: 1px solid #ddd;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 10px 15px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background-color: #f9f9f9;
            width: 30%;
        }
        tr:nth-child(even) {
            background-color: #f4f4f4;
        }
        .logo {
            max-width: 150px;
            margin: 15px auto;
            display: block;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>New Form Submission</h1>
    </div>

    @php
        $contact = $array['contact'];
    @endphp

    <div class="content">
        <p>Hello Admin,</p>
        <p>A new form has been submitted on your website. Below are the submitted details:</p>

        @if(!empty($contact['theme_logo']))
            <img src="{{ $contact['theme_logo'] }}" alt="Theme Logo" class="logo">
        @endif

        <table>
            @foreach($contact as $key => $value)
                @if($value && !Str::startsWith($key, 'button-') && !in_array($key, ['theme_name', 'theme_logo','template_subject']))
                    <tr>
                        <th>{{ ucwords(str_replace(['_', '-'], ' ', $key)) }}</th>
                        <td>{{ $value }}</td>
                    </tr>
                @endif
            @endforeach
        </table>

        <p>Please follow up accordingly.</p>

        <p>Regards,<br>
        NKS Parts</p>
    </div>

    <div class="footer">
        &copy; {{ date('Y') }} NKS Parts. All rights reserved.
    </div>
</div>
</body>
</html>
