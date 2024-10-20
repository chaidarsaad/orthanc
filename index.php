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
            padding: 20px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        label {
            margin: 10px 0 5px;
        }

        input[type="text"],
        input[type="date"],
        input[type="submit"] {
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        input[type="submit"] {
            background: #5cb85c;
            color: white;
            border: none;
            cursor: pointer;
        }

        input[type="submit"]:hover {
            background: #4cae4c;
        }

        .results {
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <form action="" method="post">
            <label for="patientID">Patient ID:</label>
            <input type="text" id="patientID" name="patientID" required>

            <label for="startDate">Start Date (YYYY-MM-DD):</label>
            <input type="date" id="startDate" name="startDate" required>

            <label for="endDate">End Date (YYYY-MM-DD):</label>
            <input type="date" id="endDate" name="endDate" required>

            <input type="submit" value="Cari">
        </form>

        <div class="results">
            <?php
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                $patientID = $_POST['patientID'];
                $startDate = $_POST['startDate'];
                $endDate = $_POST['endDate'];

                $results = [];

                $start = new DateTime($startDate);
                $end = new DateTime($endDate);
                $interval = new DateInterval('P1D');
                $period = new DatePeriod($start, $interval, $end->modify('+1 day'));

                foreach ($period as $date) {
                    $currentDate = $date->format('m-d-Y');

                    $url = 'http://localhost:8042/tools/find';
                    $data = json_encode([
                        'Level' => 'Instance',
                        'Query' => [
                            'PatientID' => $patientID,
                            'PatientName' => '*',
                            'Modality' => '*',
                            'StudyDate' => $currentDate
                        ],
                        'Expand' => true
                    ]);

                    $ch = curl_init($url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Content-Type: application/json',
                        'Content-Length: ' . strlen($data)
                    ]);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

                    $response = curl_exec($ch);

                    if (curl_errno($ch)) {
                        echo 'cURL Error: ' . curl_error($ch);
                        curl_close($ch);
                        exit;
                    }

                    $result = json_decode($response, true);
                    if (!empty($result)) {
                        $results = array_merge($results, $result);
                    }

                    curl_close($ch);
                }

                if (!empty($results)) {
                    echo '<h1>Hasil Pencarian</h1>';
                    $totalResults = count($results);
                    echo "<h2>Total Hasil Pencarian: $totalResults</h2>";
                    foreach ($results as $instance) {
                        $fileUuid = $instance['FileUuid'];
                        $fileSize = $instance['FileSize'];

                        echo '<pre>';
                        echo json_encode([
                            'FileSize' => $fileSize,
                            'FileUuid' => $fileUuid,
                            'ID' => $instance['ID'],
                            'IndexInSeries' => $instance['IndexInSeries'],
                            'MainDicomTags' => $instance['MainDicomTags'],
                            'ParentSeries' => $instance['ParentSeries'],
                            'Type' => $instance['Type']
                        ], JSON_PRETTY_PRINT);
                        echo '</pre>';
                    }
                } else {
                    echo 'Tidak ditemukan data untuk kriteria pencarian ini.';
                }
            }
            ?>
        </div>
    </div>
</body>

</html>