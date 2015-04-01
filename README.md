То что тесты проходят успешно это ещё ни о чем не говорит (c)

[![Build Status](https://travis-ci.org/ZFStarter/ZFSTool.svg?branch=master)](https://travis-ci.org/ZFStarter/ZFSTool)

[![Dependency Status](https://www.versioneye.com/user/projects/5320a03fec1375be8b00034d/badge.png)](https://www.versioneye.com/user/projects/5320a03fec1375be8b00034d)

[![Latest Stable Version](https://poser.pugx.org/zfstarter/zfs-tool/v/stable.png)](https://packagist.org/packages/zfstarter/zfs-tool)
[![Total Downloads](https://poser.pugx.org/zfstarter/zfs-tool/downloads.png)](https://packagist.org/packages/nzfstarter/zfs-tool)
[![Latest Unstable Version](https://poser.pugx.org/zfstarter/zfs-tool/v/unstable.png)](https://packagist.org/packages/zfstarter/zfs-tool)
[![License](https://poser.pugx.org/zfstarter/zfs-tool/license.png)](https://packagist.org/packages/zfstarter/zfs-tool)

[![Coverage Status](https://coveralls.io/repos/zfstarter/zfs-tool/badge.png?branch=master)](https://coveralls.io/r/zfstarter/zfs-tool?branch=master)

ZFCTool - Zend Framework 2 command line Tool

------------------------------------------------------------------------------------------------------------

###Установка:

Добавляем в `composer.json`:

```json
{
    "require-dev": {
        "zfstarter/zfs-tool": "dev-master"
    }
}
```

И обновляем зависимость:

    $ curl -s http://getcomposer.org/installer | php
    $ php composer.phar update


В config\autoload\global.php

указываем, если нужно, директорию где находятся модули, как будет называться директория с миграциямии и таблица в БД:

```php

// Указать массивом список директорий
'ZFCTool' => array(
        'migrations' => array(
            'modulesDirectoryPath' => array(
                'module',
                'vendor/zfstarter'
            )
        )
    )

// Либо строкой
'ZFCTool' => array(
        'migrations' => array(
            'modulesDirectoryPath' => 'module'
        )
    )

```

###Работа с миграциями:


###Пример работы с миграциями:

####Создание миграции
Если необходимо сгенерировать миграцию для определенного модуля, когда под него таблица в БД уже создана:
```bash
php vendor/bin/zfc.php gen migration --module=Pages --whitelist=pages -c
```
при этом указываем имя модуля (регистр важен),
а также имя нужной таблицы задаем в "whitelist", если не хотите добавлять лишнии таблици в миграцию,
сразу же комитем эту миграцию, для этого параметр "-c"

####Применение миграции

Если при апдейте кода к вам "пришла" новая миграция, то чтобы обновить БД вам нжно выполнить всего одну команду:

```bash
php vendor/bin/zfc.php up db [-i]
```
если не указывать дополнительных параметров, то применяться все существующие миграции.
`-i`  - (Опциональный) применить миграции всех модулей, включая корневые миграции

####Список всех миграций

```bash
  ~$ php vendor/bin/zfc.php ls migrations [--module]
  ~$ php vendor/bin/zfc.php ls migrations [-i]
```
`module`  - (Опциональный) вывести только миграции указанного модуля
`-i`  - (Опциональный) вывести миграции всех модулей, включая корневые

####Обновить БД к указанной миграции

```bash
  ~$ php vendor/bin/zfc.php up db <to> [--module]
  ~$ php vendor/bin/zfc.php up db <to> [-i]
```
`module`  - (Опциональный) название модуля, к которому будет применяться (если не указан, то применяеться ко всем миграциям)
`-i`  - (Опциональный) поиск миграции по всем модулям, включая корневые миграции
`to`  - (Опциональный) имя миграции к которой обновить (если не указан, то обновить к последней)

####Откатить миграции все мигрции до указанной, включая её

```bash
  ~$ php vendor/bin/zfc.php down db <to> [--module]
  ~$ php vendor/bin/zfc.php down db <to> [-i]
```
`module`  - (Опциональный) название модуля, к которому будет применяться (если не указан, то применяеться ко всем миграциям)
`-i`  - (Опциональный) поиск миграции по всем модулям, включая корневые миграции
`to`  - (Опциональный) имя миграции, которую откатить (если не указан, то откатить все)
  
  
  
####Показать текущую миграцию
```bash
  ~$ php vendor/bin/zfc.php show migration
```

####Сгенерировать миграцию

```bash
  ~$ php vendor/bin/zfc.php gen migration [--module] [--whitelist] [--blacklist] [-c] [-e]
```
`module`  - (Опциональный) название модуля, для которого сгенерируеться миграция

`module`  - (Опциональный) название модуля, для которого сгенерируеться миграция
`whitelist`  - (Опциональный) список таблиц, для которых генерить
`blacklist`  - (Опциональный) список таблиц исключения
`c`  - (Опциональный) сразу же после создания применить миграцию
`e`  - (Опциональный) создать пустой шаблон миграции


####Принудительное применение миграции

```bash
  ~$ php vendor/bin/zfc.php ci migration <to> [--module]
  ~$ php vendor/bin/zfc.php ci migration <to> [-i]
```
`module`  - (Опциональный) название модуля, где находиться миграция
`-i`  - (Опциональный) поиск миграции по всем модулям, включая корневые миграции
`to`  - имя миграции, которую следует применить


####Откат мигрций
```bash
  ~$ php vendor/bin/zfc.php back db [--module] [--step]
  ~$ php vendor/bin/zfc.php back db [-i] [--step]
```

`module`  - (Опциональный) название модуля
`-i`  - (Опциональный) поиск миграции по всем модулям, включая корневые миграции
`step`  - (Опциональный) количество откатываемых миграций


####Показать различие в стрцктуре БД между последним обновлением и текущим состоянием
```bash
  ~$ php vendor/bin/zfc.php diff db [--module] [--whitelist] [--blacklist]
```

`module`  - (Опциональный) название модуля, для которого сгенерируеться миграция
`whitelist`  - (Опциональный) список таблиц, для которых генерить
`blacklist`  - (Опциональный) список таблиц исключения


###Работа с дампом БД:

####Список всех дампов

```bash
  ~$ php vendor/bin/zfc.php ls dump [-i]
```
`-i`  - (Опциональный) вывести дампы всех модулей, включая корневые

####Создание дампа БД:

```bash
  ~$ php vendor/bin/zfc.php create dump [--module] [--name] [--whitelist] [--blacklist]
```

`name`  - (Опциональный) имя дампа
`module`  - (Опциональный) название модуля, для которого сгенерируеться дамп
`whitelist`  - (Опциональный) список таблиц, для которых генерить
`blacklist`  - (Опциональный) список таблиц исключения

####Импорт дампа БД:
```bash
  ~$ php vendor/bin/zfc.php import dump <name> [--module]
```

`module`  - (Опциональный) название модуля, для которого сгенерируеться дамп
`name`  - (Опциональный) имя дампа


## Support

If you believe you have found a bug, please report it using the [GitHub issue tracker](https://github.com/naxel/ZFCTool/issues),
or better yet, fork the library and submit a pull request.
