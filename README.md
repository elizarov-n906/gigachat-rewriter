# GigaChat Рерайт Постов

<p align="center">
  <img src="https://raw.githubusercontent.com/elizarov-n906/gigachat-rewriter/main/assets/banner-1544x500.png" alt="GigaChat Рерайт Постов — массовый рерайт через API Сбера" width="100%" style="max-width: 100%;">
</p>

<div align="center">

## Массовый рерайт записей WordPress через GigaChat API

**GigaChat Рерайт Постов** — инструмент для автоматического переписывания заголовков и текстов статей с помощью нейросети GigaChat. Идеально подходит для SEO-оптимизации и обновления старого контента.

![Version](https://img.shields.io/badge/version-2.0.3-blue?style=flat-square)
![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-21759b?style=flat-square&logo=wordpress)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4?style=flat-square&logo=php)
![License](https://img.shields.io/badge/license-GPLv2-green?style=flat-square)

[Описание](#о-плагине) · [Установка](#установка) · [Использование](#использование) · [Лицензия](#лицензия)

</div>

---

## О плагине

Плагин позволяет выбрать конкретные опубликованные записи и запустить их массовый рерайт прямо из админки. Поддерживает раздельные промпты для заголовков и основного текста, учет токенов и детальное логирование.

### Возможности:
*   Раздельная настройка промптов для заголовка и контента.
*   Поддержка всех типов авторизации GigaChat (PERS, B2B, CORP).
*   Автоматическая очистка ответа от рассуждений модели и мусора.
*   Сохранение структуры HTML, изображений и подзаголовков.
*   Детальная статистика по затраченным токенам для каждой статьи.
*   Отладочный лог в папке uploads для анализа ошибок API.
*   Безопасная работа через стандартный WordPress AJAX.

---

## Установка

1. Скачайте ZIP-архив из раздела **Releases**.
2. Перейдите в админке: **Плагины → Добавить новый → Загрузить плагин**.
3. Выберите файл `gigachat-rewriter.zip` и нажмите **Установить**.
4. Активируйте плагин.
5. Перейдите в **Консоль → GigaChat Рерайт** для настройки.

---

## Использование

1. Получите Authorization Key в [Sber AI Studio](https://developers.sber.ru/studio).
2. Вставьте ключ, выберите Scope и модель в настройках плагина.
3. Настройте промпты под ваши задачи (по умолчанию уже заданы качественные шаблоны).
4. На вкладке "Список записей" выберите нужные статьи галочками.
5. Нажмите **"Рерайт выбранных"** и дождитесь завершения обработки.

---

## Скриншоты

<p align="center">
  <img src="https://raw.githubusercontent.com/elizarov-n906/gigachat-rewriter/main/assets/screenshot-1.jpg" alt="Настройки подключения и промптов" width="80%">
  <br><em>Настройки подключения и промптов</em>
</p>

<p align="center">
  <img src="https://raw.githubusercontent.com/elizarov-n906/gigachat-rewriter/main/assets/screenshot-2.jpg" alt="Список записей для рерайта" width="80%">
  <br><em>Выбор записей и запуск массового рерайта</em>
</p>

---

## Лицензия

Copyright © 2024 Elizarov_n906

Этот плагин распространяется под лицензией **GNU General Public License v2 (GPLv2)**.  
Это означает, что вы можете свободно использовать, изменять и распространять плагин при условии сохранения открытости исходного кода.

<div align="center">
  <br>
  Разработано — <a href="https://github.com/elizarov-n906">Elizarov_n906</a>
</div>