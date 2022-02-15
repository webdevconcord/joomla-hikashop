# Модуль ConcordPay для Joomla HikaShop

Creator: [ConcordPay](https://concordpay.concord.ua)<br>
Tags: ConcordPay, Joomla HikaShop, payment, payment gateway, credit card, Visa, MasterCard, Apple Pay, Google Pay<br>
Requires at least: Joomla 3.8, HikaShop 4.4<br>
License: GNU GPL v3.0<br>
License URI: [License](https://opensource.org/licenses/GPL-3.0)

Этот модуль позволит вам принимать платежи через платёжную систему **ConcordPay**.

Для работы модуля у вас должны быть установлены **CMS Joomla 4.x** и модуль электронной коммерции **HikaShop 4.x**.

## Установка

### Установка через загрузку модуля

1. В административной части сайта перейти в *«Система -> Расширения -> Загрузить файл пакета»* и загрузить архив с модулем, 
который находится в папке `package`.

2. Перейти в *«Система -> Плагины»*, включить плагин *«HikaShop - ConcordPay Payment Gateway»*.

3. Перейти в *«Компоненты -> HikaShop -> Конфигурация -> Система -> Способ оплаты»*, нажать кнопку **«Создать»**.

4. Из списка выбрать плагин *«HikaShop - ConcordPay Payment Gateway»*.

5. Установить необходимые настройки плагина.<br>
   Опубликовано: **ДА**<br>
   Цена: **UAH**<br>
   Валюта: **UAH**

   Также указать данные, полученные от платёжной системы:
    - *Идентификатор продавца (Merchant ID)*;
    - *Секретный ключ (Secret Key)*.

6. Сохранить настройки модуля.

### Установка вручную

1. Распаковать архив с модулем и перенести его содержимое в папку `{YOUR_SITE}/plugins/hikashoppayment/concordpay`.

2. Опционально: скопировать файлы логотипов систем оплаты из папки `{YOUR_SITE}/plugins/hikashoppayment/concordpay/media/images/payment`
в `{YOUR_SITE}/media/com_hikashop/images/payment`.  
   
3. Импортировать в базу данных запрос из файла `plugins/hikashoppayment/concordpay/sql/install.mysql.utf8.sql`,
исправив префикс названия таблицы на префикс таблиц в вашей базе данных (вместо `#__extensions` должно получиться что-то вроде `x5rhr_extensions`).

4. Выполнить пп. 2-6 Установки через загрузку модуля.

Модуль готов к работе.

*Модуль Joomla HikaShop протестирован для работы с Joomla 4.0.6, HikaShop 4.4.4 и PHP 7.4.*