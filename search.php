<?php

$setting = [
    'url' => 'api.php', // Путь к API
    'login' => 'test', // Логин
    'password' => '123' // Пароль
];

// Формируем токен для доступа к API
// В целях безопасности для авторизации в API будет переден логин пользователя и токен (хэш из логина и пароля)
$token = md5($setting['login'] . $setting['password']);

// Строка с поисковым запросом
$search = empty($_POST['term']) ? false : $_POST['term'];
if (!$search) exit('Ошибка! Не задан поисковый запрос!');

if ($ch = curl_init()) {
    curl_setopt($ch, CURLOPT_URL, $setting['url']);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'token=' . $token . '&search=' . $search);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $server_output = curl_exec($ch);
    curl_close ($ch);
    if ($server_output) {
        $json = json_decode($server_output);
        if ($json->status == 'success') {
            echo str_replace(['{"0":', '}'], '', json_encode($json->result));
        } elseif ($json->status == 'error') {
            exit('Ошибка: ' . $json->message);
        }
    }
}