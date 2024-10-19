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
} else {
    echo "Metode tidak diizinkan.";
}
