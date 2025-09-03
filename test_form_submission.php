<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Test data that matches what the Flutter app sends
$testData = [
    'name' => 'Test User',
    'email' => 'test@example.com',
    'phone' => '1234567890',
    'education' => 'B.Tech',
    'experience' => '2 years',
    'skills' => 'PHP, MySQL, Flutter',
    'job_id' => 1,
    'referral_code' => 'TEST123',
    'photo_data' => 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAYEBQYFBAYGBQYHBwYIChAKCgkJChQODwwQFxQYGBcUFhYaHSUfGhsjHBYWICwgIyYnKSopGR8tMC0oMCUoKSj/2wBDAQcHBwoIChMKChMoGhYaKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCj/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCdABmX/9k=',
    'photo_name' => 'test_photo.jpg',
    'resume_data' => 'data:application/pdf;base64,JVBERi0xLjQKJcOkw7zDtsO8DQoxIDAgb2JqDQo8PA0KL1R5cGUgL0NhdGFsb2cNCi9QYWdlcyAyIDAgUg0KPj4NCmVuZG9iag0KMiAwIG9iag0KPDwNCi9UeXBlIC9QYWdlcw0KL0NvdW50IDENCi9LaWRzIFs0IDAgUl0NCj4+DQplbmRvYmoNCjQgMCBvYmoNCjw8DQovVHlwZSAvUGFnZQ0KL1BhcmVudCAyIDAgUg0KL1Jlc291cmNlcyA8PA0KL0ZvbnQgPDwNCi9GMSA1IDAgUg0KPj4NCi9FeHRHU3RhdGUgPDwNCi9QYXR0ZXJuIDYgMCBSDQo+Pg0KPj4NCi9NZWRpYUJveCBbMCAwIDU5NSA4NDJdDQovQ29udGVudHMgNiAwIFINCj4+DQplbmRvYq0=',
    'resume_name' => 'test_resume.pdf',
    'company_name' => 'Test Company',
    'package' => '10 LPA',
    'profile' => 'Software Developer',
    'district' => 'Mumbai'
];

echo "Testing form submission with data:\n";
echo json_encode($testData, JSON_PRETTY_PRINT);
echo "\n\n";

// Test the endpoint
$url = 'https://playsmart.co.in/submit_job_application_fixed.php';
$data = json_encode($testData);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($data)
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);

echo "Sending request to: $url\n";
echo "Request data length: " . strlen($data) . " bytes\n\n";

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

echo "Response HTTP Code: $httpCode\n";
if ($error) {
    echo "cURL Error: $error\n";
}
echo "Response Body:\n$response\n";

// Check if response is valid JSON
$responseData = json_decode($response, true);
if ($responseData === null) {
    echo "\nResponse is not valid JSON. JSON Error: " . json_last_error_msg() . "\n";
} else {
    echo "\nResponse parsed successfully:\n";
    echo json_encode($responseData, JSON_PRETTY_PRINT);
}
?> 