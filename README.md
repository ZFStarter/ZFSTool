То что тесты проходят успешно это ещё ни о чем не говорит (c)

[![Build Status](https://travis-ci.org/naxel/ZFCTool.png?branch=master)](https://travis-ci.org/naxel/ZFCTool)

[![Dependency Status](https://www.versioneye.com/user/projects/5320a03fec1375be8b00034d/badge.png)](https://www.versioneye.com/user/projects/5320a03fec1375be8b00034d)

[![Latest Stable Version](https://poser.pugx.org/naxel/zfctool/v/stable.png)](https://packagist.org/packages/naxel/zfctool)
[![Total Downloads](https://poser.pugx.org/naxel/zfctool/downloads.png)](https://packagist.org/packages/naxel/zfctool)
[![Latest Unstable Version](https://poser.pugx.org/naxel/zfctool/v/unstable.png)](https://packagist.org/packages/naxel/zfctool)
[![License](https://poser.pugx.org/naxel/zfctool/license.png)](https://packagist.org/packages/naxel/zfctool)

[![Coverage Status](https://coveralls.io/repos/naxel/ZFCTool/badge.png?branch=master)](https://coveralls.io/r/naxel/ZFCTool?branch=master)

ZFCTool - Zend Framework 2 command line Tool

------------------------------------------------------------------------------------------------------------

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
php vendor/bin/zfc.php up db
```
если не указывать дополнительных параметров, то применяться все существующие миграции.
####Список всех миграций

```bash
  ~$ php vendor/bin/zfc.php ls migrations [--module]
```
`module`  - (Опциональный) вывести только миграции указанного модуля


####Обновить БД к указанной миграции

```bash
  ~$ php vendor/bin/zfc.php up db <to> [--module]
```
`module`  - (Опциональный) название модуля, к которому будет применяться (если не указан, то применяеться ко всем миграциям)
`to`  - (Опциональный) имя миграции к которой обновить (если не указан, то обновить к последней)


####Откатить миграции все мигрции до указанной, включая её

```bash
  ~$ php vendor/bin/zfc.php down db <to> [--module]
```
`module`  - (Опциональный) название модуля, к которому будет применяться (если не указан, то применяеться ко всем миграциям)
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
```
`module`  - (Опциональный) название модуля, где находиться миграция
`to`  - имя миграции, которую следует применить


####Откат мигрций
```bash
  ~$ php vendor/bin/zfc.php back db [--module] [--step]
```

`module`  - (Опциональный) название модуля
`step`  - (Опциональный) количество откатываемых миграций


####Показать различие в стрцктуре БД между последним обновлением и текущим состоянием
```bash
  ~$ php vendor/bin/zfc.php diff db [--module] [--whitelist] [--blacklist]
```

`module`  - (Опциональный) название модуля, для которого сгенерируеться миграция
`whitelist`  - (Опциональный) список таблиц, для которых генерить
`blacklist`  - (Опциональный) список таблиц исключения


###Работа с дампом БД:

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
