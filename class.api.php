<?php

class Api
{
    private $yandex_api_url = 'https://geocode-maps.yandex.ru/1.x/'; // Путь к Яндекс API
    private $yandex_api_key = ''; // Ключ в Яндекс API
    private $result_limit = 6; // Лимит результатов (подсказок)

    // Доступы к БД
    private $db_host = 'localhost';
    private $db_user = 'user';
    private $db_pass = 'pass';
    private $db_name = 'geo';

    function __construct($token)
    {
        // Установовим соединение с БД
        $this->pdo = new PDO('mysql:host=' . $this->db_host . ';dbname=' . $this->db_name . ';charset=utf8;', $this->db_user, $this->db_pass);
        // Проверим доступ к API
        $this->checkAccess($token);
    }

    /*
        Проверяет доступ пользователя к API
        token - это хеш логина и пароля клиента md5(login . password)
        В целях безопасности password в БД не хранится и известен только клиенту
    */
    private function checkAccess($token)
    {
        if (!$token)
            throw new RuntimeException('Ошибка: токен не передан', 404);

        $user = $this->pdo->prepare('SELECT status FROM users WHERE token = :token');
        $user->execute(['token' => $token]);

        if ($user->fetchColumn() != 'on')
            throw new RuntimeException('Ошибка: не верный токен или пользователь заблокирован', 404);
    }

    /*
        Запрос в Яндекс геокодер, возвратит 6 подходящих названий городов/регионов
     */
    private function getYandexGeo($search)
    {
        $result = [];
        if ($ch = curl_init()) {
            curl_setopt($ch, CURLOPT_URL, $this->yandex_api_url . '?apikey=' . $this->yandex_api_key . '&geocode=' . rawurlencode($search));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            $server_output = curl_exec($ch);
            curl_close($ch);
            if ($server_output) {
                // Разбор XML
                $xml = new SimpleXMLElement($server_output);
                foreach ($xml->GeoObjectCollection->featureMember as $geo) {
                    // Если такого названия гео объекта еще нет, добавим в его массив
                    if (!in_array($geo->GeoObject->metaDataProperty->GeocoderMetaData->text, $result))
                        $result[] = $geo->GeoObject->metaDataProperty->GeocoderMetaData->text;
                    // Достигнут лимит подсказок, выходим из цикла
                    if (count($result) == $this->result_limit)
                        break;
                }
            }
        }
        return $result;
    }

    /*
        Вернет гео-объекты из БД или выполнит запрос в Яндекс API
    */
    public function getGeoObjects($search)
    {
        // Убираем спец. символы и проверяем длину строки
        $search = $this->cleanStr($search);
        if (mb_strlen($search) < 3)
            return json_encode(['status' => 'error', 'message' => 'Поисковый запрос должен быть более 3-х символов']);

        $result = [];
        // Пытаемся найти запрос в БД (если есть отдаем результаты и пишем лог, если нет - делаем запрос в яндекс API и завписываем в БД)
        $geo_request = $this->pdo->prepare('SELECT id FROM geo_request WHERE request LIKE :request');
        $geo_request->execute(['request' => $search]);
        if ($id_request = $geo_request->fetchColumn()) {
            $source = 'db';
            // Достаем результаты из БД
            $geo_result = $this->pdo->prepare('SELECT result FROM geo_result WHERE id_request = :id_request ORDER BY result');
            $geo_result->execute(['id_request' => $id_request]);
            foreach ($geo_result as $row) {
                $result[] = $row['result'];
            }
        } else {
            // В БД нет результатов, отправляем запрос в Yandex
            $source = 'api';
            $geo_request = $this->pdo->prepare('INSERT INTO geo_request (request) VALUES (:request)');
            $geo_request->bindParam(':request', $search);
            $geo_request->execute();
            $id_request = $this->pdo->lastInsertId();
            if ($result = $this->getYandexGeo($search)) {
                // Добавляем результаты в БД путем массовой вставки
                $this->pdo->beginTransaction();
                $geo_result = $this->pdo->prepare('INSERT INTO geo_result (id_request, result) VALUES (:id_request, :result)');
                foreach ($result as $value) {
                    $geo_result->bindParam(':id_request', $id_request);
                    $geo_result->bindParam(':result', $value);
                    $geo_result->execute();
                }
                $this->pdo->commit();
            }
        }
        // Запишем лог
        $geo_log = $this->pdo->prepare('INSERT INTO geo_log (id_request, source, date) VALUES (:id_request, :source, now())');
        $geo_log->bindParam(':id_request', $id_request);
        $geo_log->bindParam(':source', $source);
        $geo_log->execute();

        return json_encode(['status' => 'success', 'result' => $result]);
    }

    /*
        Убирает спец-символы из строки
    */
    public function cleanStr($str)
    {
        return trim(preg_replace("/[^a-zA-ZА-Яа-я0-9\s]/u", '', $str));
    }
}