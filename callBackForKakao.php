<?php
session_start();

// 1. 인가 코드 받기
if (!isset($_GET['code'])) {
    die("Error: No authorization code provided.");
}

$code = $_GET['code'];

// 2. 클라이언트 정보 및 리다이렉트 URI 설정 (Kakao Developers 콘솔에 등록된 정보와 일치해야 함)
$client_id = "25a94ead8cc19727a665a0d9c1acdbc4"; // 네이티브 앱 키
$client_secret = "DcL1KvhEa0atE46DEXdFIWne1syFKbZI"; // 클라이언트 시크릿
$redirect_uri = urlencode("https://jayhaja.github.io/act_now_korea_join/callBackForKakao.php");

// 3. 액세스 토큰 발급을 위한 POST 데이터 준비
$postFields = http_build_query(array(
    "grant_type"    => "authorization_code",
    "client_id"     => $client_id,
    "redirect_uri"  => $redirect_uri,
    "code"          => $code,
    "client_secret" => $client_secret
));

// 4. 액세스 토큰 요청 (POST 방식)
$tokenUrl = "https://kauth.kakao.com/oauth/token";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $tokenUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$loginResponse = curl_exec($ch);
$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if ($status_code != 200) {
    die("Error: Unable to get access token. HTTP Status Code: $status_code");
}
curl_close($ch);

$tokenData = json_decode($loginResponse, true);
if (isset($tokenData['error'])) {
    die("Error getting access token: " . $tokenData['error_description']);
}

$accessToken = $tokenData['access_token'];
// 액세스 토큰을 세션에 저장 (후속 처리를 위해)
$_SESSION["accessToken"] = $accessToken;

// 5. 액세스 토큰을 사용해 사용자 정보 조회
$profileUrl = "https://kapi.kakao.com/v2/user/me";
$headers = array(
    "Authorization: Bearer " . $accessToken
);
// propertyKeys는 JSON 배열 문자열 형식이어야 합니다.
$data = array(
    'propertyKeys' => '["profile_nickname", "profile_image", "account_email", "gender", "age_range", "birthyear", "phone_number"]'
);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $profileUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$profileResponse = curl_exec($ch);
$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if ($status_code != 200) {
    die("Error: Unable to get user profile. HTTP Status Code: $status_code");
}
curl_close($ch);

$profileData = json_decode($profileResponse, true);

// 6. 결과 출력 (디버깅용)
// 실제 서비스에서는 이 정보를 DB에 저장하거나 세션에 저장하는 등의 처리를 진행합니다.
echo "<pre>";
echo "Access Token:\n";
var_dump($accessToken);
echo "\nToken Response:\n";
var_dump($loginResponse);
echo "\nUser Profile:\n";
var_dump($profileData);
echo "</pre>";
?>
