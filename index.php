<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="style.css">
</head>

<body>
    <div class="container">
        <form action="" method="post">
            <label for="patientID">Patient ID:</label>
            <input type="text" id="patientID" name="patientID" value="<?php echo isset($_POST['patientID']) ? htmlspecialchars($_POST['patientID']) : ''; ?>" required>

            <label for="startDate">Start Date:</label>
            <input type="date" id="startDate" name="startDate" value="<?php echo isset($_POST['startDate']) ? htmlspecialchars($_POST['startDate']) : ''; ?>" required>

            <label for="endDate">End Date:</label>
            <input type="date" id="endDate" name="endDate" value="<?php echo isset($_POST['endDate']) ? htmlspecialchars($_POST['endDate']) : ''; ?>" required>

            <input type="submit" value="Cari">
        </form>

        <div class="results">
            <?php
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                $patientID = $_POST['patientID'];
                $startDate = $_POST['startDate'];
                $endDate = $_POST['endDate'];

                $results = [];
                $studiesResults = [];

                $start = new DateTime($startDate);
                $end = new DateTime($endDate);
                $interval = new DateInterval('P1D');
                $period = new DatePeriod($start, $interval, $end->modify('+1 day'));

                // Fetch Instance Level Data
                foreach ($period as $date) {
                    $currentDate = $date->format('Ymd');
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
                        foreach ($result as $instance) {
                            $instance['SearchDate'] = $currentDate;
                            $results[] = $instance;
                        }
                    }

                    curl_close($ch);
                }

                // Fetch Studies Level Data
                foreach ($period as $date) {
                    $currentDate = $date->format('Ymd');
                    $url = 'http://localhost:8042/tools/find';
                    $data = json_encode([
                        'Level' => 'Study',
                        'Query' => [
                            'PatientID' => $patientID,
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

                    $studyResult = json_decode($response, true);

                    if (!empty($studyResult)) {
                        foreach ($studyResult as $study) {
                            $study['SearchDate'] = $currentDate;
                            $studiesResults[] = $study;
                        }
                    }

                    curl_close($ch);
                }

                // Display Results
                if (!empty($results)) {
                    echo "<h1>Hasil Pencarian Pasien ID: $patientID</h1>";
                    $startDateFormatted = (new DateTime($startDate))->format('Ymd');
                    $endDateFormatted = (new DateTime($endDate))->format('Ymd');
                    echo "<h2>Start Date: $startDateFormatted | End Date: $endDateFormatted</h2>";

                    foreach ($results as $instance) {
                        $fileUuid = $instance['FileUuid'];
                        $fileSize = $instance['FileSize'];
                        $searchDate = $instance['SearchDate'];

                        echo "<h4>Hasil Instance untuk tanggal: $searchDate</h4>";
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

                // Display Study Data
                if (!empty($studiesResults)) {
                    foreach ($studiesResults as $study) {
                        echo "<h4>Hasil Studi untuk tanggal: " . $study['MainDicomTags']['StudyDate'] . "</h4>";
                        echo '<pre>';
                        echo json_encode($study, JSON_PRETTY_PRINT);
                        echo '</pre>';

                        echo '<div>';
                        echo "<a href='http://localhost:8042/app/explorer.html#patient?uuid=$parentPatientUuid' target='_blank'>
                                <button type='button'>Explorer</button>
                            </a>";
                        echo "<a href='http://localhost:8042/volview/index.html?names=[archive.zip]&urls=[../studies/$studyID/archive]' target='_blank'>
                <button type='button'>Volview</button>
              </a>";
                        echo '</div>';
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