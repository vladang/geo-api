-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1:3306
-- Время создания: Июн 17 2020 г., 01:19
-- Версия сервера: 10.3.13-MariaDB-log
-- Версия PHP: 7.3.9

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

--
-- База данных: `test`
--

-- --------------------------------------------------------

--
-- Структура таблицы `geo_log`
--

CREATE TABLE `geo_log` (
  `id_request` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `source` enum('api','db') NOT NULL DEFAULT 'api' COMMENT 'Источник подсказок (яндекс API или БД)',
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'Дата запроса'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Логи запросов в гео сервис';

-- --------------------------------------------------------

--
-- Структура таблицы `geo_request`
--

CREATE TABLE `geo_request` (
  `id` int(10) NOT NULL,
  `request` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Запросы в гео-сервис';

-- --------------------------------------------------------

--
-- Структура таблицы `geo_result`
--

CREATE TABLE `geo_result` (
  `id_request` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `result` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Результаты запроса из гео-сервиса';

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int(10) NOT NULL,
  `login` varchar(30) NOT NULL,
  `token` varchar(32) NOT NULL,
  `status` enum('on','off') NOT NULL COMMENT 'Статус пользователя (вкл/выкл)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Пользователи';

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `login`, `token`, `status`) VALUES
(1, 'test', 'cc03e747a6afbbcbf8be7668acfebee5', 'on');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `geo_log`
--
ALTER TABLE `geo_log`
  ADD KEY `id_request` (`id_request`),
  ADD KEY `source` (`source`);

--
-- Индексы таблицы `geo_request`
--
ALTER TABLE `geo_request`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request` (`request`);

--
-- Индексы таблицы `geo_result`
--
ALTER TABLE `geo_result`
  ADD KEY `id_request` (`id_request`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `login` (`login`),
  ADD KEY `token` (`token`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `geo_request`
--
ALTER TABLE `geo_request`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;
