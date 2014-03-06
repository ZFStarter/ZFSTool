
То что тесты проходят успешно это ещё ни о чем не говорит (c)

[![Build Status](https://travis-ci.org/naxel/ZFCTool.png?branch=master)](https://travis-ci.org/naxel/ZFCTool)

#### Создание миграций

```bash
# создание пустой миграции
~$ php vendor/bin/zfc.php create migration

# создание пустой миграции c меткой и описанием
~$ php vendor/bin/zfc.php create migration --label=MyMigration --desc="This is my first migration"

# создание пустой миграции для модуля с именем <module>
~$ php vendor/bin/zfc.php create migration <module>

# генерация новой глобальной миграции
~$ php vendor/bin/zfc.php generate migration

# генерация новой миграции для модуля menu,
# при этом рекомендуется указывать список таблиц модуля с помощью параметра --whitelist
~$ php vendor/bin/zfc.php generate migration --module=menu --whitelist=menu

# также можно исключать тавлицы с помощью "черного списка"
~$ php vendor/bin/zfc.php generate migration --blacklist=menu

# если необходимо указать в параметрах --blacklist или --whitelist
# несколько таблиц, то просто перечислите их через запятую
~$ php vendor/bin/zfc.php generate migration --blacklist=menu,users

# для того чтобы просмотреть генерируемые запросы без создания миграции, используйте:
~$ php vendor/bin/zfc.php diff migration --blacklist=menu,users
```

#### Применение и откат миграций

```bash
# show global migrations list
~$ php vendor/bin/zfc.php listing migration
# show migrations list for module with name <module>
~$ php vendor/bin/zfc.php listing migration <module>

# migrate to last global migration
~$ php vendor/bin/zfc.php up migration
# migrate to last migration from module with name <module>
~$ php vendor/bin/zfc.php up migration <module>

# migrate to selected global migration
~$ php vendor/bin/zfc.php up migration <migration>
# migrate to selected migration from module with name <module>
~$ php vendor/bin/zfc.php up migration <module> <migration>

# fake upgrade selected global migration
~$ php vendor/bin/zfc.php fake migration <migration>
# fake upgrade selected migration from module with name <module>
~$ php vendor/bin/zfc.php fake migration <module> <migration>

# show current global migration
~$ php vendor/bin/zfc.php current migration
# show current migration for module with name <module>
~$ php vendor/bin/zfc.php current migration <module>

# rollback last global migration
~$ php vendor/bin/zfc.php rollback migration
# rollback last migration from module with name <module>
~$ php vendor/bin/zfc.php rollback migration <module>

# rollback last <step> global migrations
~$ php vendor/bin/zfc.php rollback migration <step>
# rollback last <step> migrations from module with name <module>
~$ php vendor/bin/zfc.php rollback migration <module> <step>

# downgrade all global migrations
~$ php vendor/bin/zfc.php down migration
# downgrade all migrations from module with name <module>
~$ php vendor/bin/zfc.php down migration <module>

# downgrade to selected global migration
~$ php vendor/bin/zfc.php down migration <migration>
# downgrade to selected migration from module with name <module>
~$ php vendor/bin/zfc.php down migration <module> <migration>
```

#### Создание и импорт дампов

```bash
# общий синтаксис команды создания дампа:
# create dump [<module>] [--module] [--name] [--whitelist] [--blacklist]
# [<module>] - название модуля, в папке которого будет создан дамп,
# если не указано, будет создан глобальный дамп в папке «dumps»
# [--module] - альтернативный способ указания имени модуля
# [--name] - название файла в который будет записан дамп,
# по умолчанию название формируется на основе текущей даты
# [--whitelist]/[--blacklist] - «белый»/«черный» список таблиц БД,
# которые будут добавлены/исключены из дампа
~$ php vendor/bin/zfc.php create dump --module=menu --name=menu_dump.sql --whitelist=menu

# создание глобального дампа с именем в формате YYYYMMDD_HHIISS_SS.sql
~$ php vendor/bin/zfc.php create dump

# импорт уже созданного дампа
~$ php vendor/bin/zfc.php import dump --module=menu --name=menu_dump.sql
```
