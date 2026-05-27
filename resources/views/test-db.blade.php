<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
            background-color: #f4f4f9;
            color: #333;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .status {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-weight: bold;
        }
        .status.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #007bff;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Kiểm tra kết nối Database</h1>
        
        <div class="status {{ count($movies) > 0 ? 'success' : 'error' }}">
            {{ $dbStatus }}
        </div>

        @if(count($movies) > 0)
            <h2>Dữ liệu từ bảng Movies (10 dòng đầu tiên)</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tên phim (Title)</th>
                        <th>Trạng thái (Status)</th>
                        <th>Ngày phát hành</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($movies as $movie)
                        <tr>
                            <td>{{ $movie->id }}</td>
                            <td>{{ $movie->title }}</td>
                            <td>{{ $movie->status }}</td>
                            <td>{{ $movie->release_date ?? 'N/A' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @elseif(strpos($dbStatus, 'Kết nối thành công') !== false)
            <p>Kết nối database thành công nhưng bảng <strong>movies</strong> hiện không có dữ liệu.</p>
        @endif
    </div>
</body>
</html>
