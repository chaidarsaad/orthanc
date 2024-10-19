<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $patientID = $_POST['patientID'];
    $startDate = $_POST['startDate'];
    $endDate = $_POST['endDate'];

    $url = 'http://localhost:8042/tools/find';
    $data = json_encode([
        'Level' => 'Instance',
        'Query' => [
            'PatientID' => $patientID,
            'StudyDate' => $startDate . '-' . $endDate,
            'PatientName' => '*',
            'Modality' => '*'
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
    } else {
        $result = json_decode($response, true);
        if (!empty($result)) {
            echo '<h1>Hasil Pencarian</h1>';

            foreach ($result as $instance) {
                $fileUuid = $instance['FileUuid'];
                $fileSize = $instance['FileSize'];
                $mainDicomTags = $instance['MainDicomTags'];

                // Tampilkan informasi JSON
                echo '<pre>';
                echo json_encode([
                    'FileSize' => $fileSize,
                    'FileUuid' => $fileUuid,
                    'ID' => $instance['ID'],
                    'IndexInSeries' => $instance['IndexInSeries'],
                    'MainDicomTags' => $mainDicomTags,
                    'ParentSeries' => $instance['ParentSeries'],
                    'Type' => $instance['Type']
                ], JSON_PRETTY_PRINT);
                echo '</pre>';

                // Menampilkan gambar menggunakan UUID
                echo '<img src="http://localhost:8042/instances/' . $fileUuid . '/preview" alt="Preview Gambar" />';
            }
        } else {
            echo 'Tidak ditemukan data untuk kriteria pencarian ini.';
        }
    }

    curl_close($ch);
} else {
    echo "Metode tidak diizinkan.";
}
