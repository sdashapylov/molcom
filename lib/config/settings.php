<?php
return [
    'host' => [
        'title' => 'IP-адрес сервера',
        'description' => 'Укажите IP-адрес или доменное имя сервера для подключения по SSH.',
        'value' => '',
        'control_type' => waHtmlControl::INPUT,
    ],
    'user' => [
        'title' => 'Логин',
        'description' => 'Имя пользователя для подключения к серверу по SSH.',
        'value' => '',
        'control_type' => waHtmlControl::INPUT,
    ],
    'password' => [
        'title' => 'Пароль',
        'description' => 'Пароль пользователя для подключения к серверу по SSH.',
        'value' => '',
        'control_type' => waHtmlControl::INPUT,
    ],
    'owner_code' => [
        'title' => 'Ваш OWNERCODE',
        'description' => 'AM – значение (краткое наименование), которое соответствует коду бренда. В вашем случае соответствует сайту animalpak.ru.',
        'value' => '',
        'control_type' => waHtmlControl::INPUT,
    ],
    'owner_inn' => [
        'title' => 'Ваш ИНН',
        'description' => 'Укажите ИНН вашей организации или индивидуального предпринимателя.',
        'value' => '',
        'control_type' => waHtmlControl::INPUT,
    ],

    'save_path' => [
        'title' => 'Путь на FTP',
        'description' => 'Введите каталог куда нужно сохранить XML',
        'value' => '',
        'control_type' => waHtmlControl::INPUT,
    ],
];