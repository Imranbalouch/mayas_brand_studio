<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border: 1px solid #dddddd;
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            background-color: #746f6f;
            color: #ffffff;
            padding: 10px;
            border-radius: 5px 5px 0 0;
            text-align: center;
        }
        .content {
            padding: 20px;
            text-align: center;
        }
        .footer {
            text-align: center;
            padding: 10px;
            font-size: 12px;
            color: #888888;
            border-top: 1px solid #dddddd;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }

    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1> New Inquiry </h1>
        </div>
        <div class="content">
            
          <h2>New Inquiry Received</h2>

          <p>You have received a new inquiry with the following details:</p>

          <table>

              <tr>
                  <th>Field</th>
                  <th>Details</th>
              </tr>
              <tr>
                  <td><strong>Name</strong></td>
                  <td>{{ $details['inquiry']->name }}</td>
              </tr>
              <tr>
                  <td><strong>Email</strong></td>
                  <td>{{ $details['inquiry']->email }}</td>
              </tr>
              <tr>
                  <td><strong>Phone</strong></td>
                  <td>{{ $details['inquiry']->phone }}</td>
              </tr>
              <tr>
                  <td><strong>Message</strong></td>
                  <td>{{ $details['inquiry']->message }}</td>
              </tr>
              <tr>
                  <td><strong>IP Address</strong></td>
                  <td>{{ $details['inquiry']->ip_address }}</td>
              </tr>
              
              
          </table>
            
            <br><br>

            <p>Best regards,<br> Digital Graphiks </p>
        </div>
        <div class="footer">
            <p>&copy; 2024 Digital Graphiks. All rights reserved.</p>
        </div>
    </div>
</body>
</html>