<?php
require_once 'vendor/autoload.php';
use \Firebase\JWT\JWT;
use Firebase\JWT\Key;

$jwt = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpZCI6InRlc3QtdXNlci1pZCIsInRva2VuSWQiOiIyOTFiNDhiZS0zMzM3LTQxODUtYTcxNS1hMGRkMTE4YjE2NGQiLCJpYXQiOjE3MDE5NDgwMDgsImV4cCI6MTcwMTk4NDAwOH0.4gTvbIFtngE6fsDdK8T-6KPjf8iM8VGKrLZOz3Z1n7w";
$secretKey = "k34j6es9fdvqo23k412qqa7u5743";

try {
    $headers = new stdClass();
    $decoded = JWT::decode($jwt, new Key($secretKey, 'HS256'), $headers);
    print_r($headers);
    print_r($decoded);
} catch (\Firebase\JWT\ExpiredException $e) {
    echo 'Caught expired token exception: ',  $e->getMessage(), "\n";
} catch (\Firebase\JWT\SignatureInvalidException $e) {
    echo 'Caught invalid signature exception: ',  $e->getMessage(), "\n";
} catch (\Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}