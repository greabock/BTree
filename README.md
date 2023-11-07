# Тестовое задание для Rerum Fincance s.r.o

## Требуется:

1. По набору документов и заданному полю сформировать бинарное сбалансированное дерево поиска (поисковый индекс); в том случае, если в документе поле не представлено, он в индекс не попадает
2. Сохранить индекс в отдельный файл в любом формате на ваше усмотрение
3. Реализовать поиск по набору документов как с использованием индекса, так и без него (последовательным перебором); условие поиска - строгое соответствие; поиск должен выводить найденные документы (если таковые есть), а также количество операций сравнения, которые понадобились для их обнаружения

Интерфейс пользователя (GUI / CLI) - на усмотрение выполняющего. 

Пример файла с исходными данными:

```
[
{"name":"Aachen","id":"1","nametype":"Valid","recclass":"L5","mass":"21","fall":"Fell","year":"1880-01-01T00:00:00.000","reclat":"50.775000","reclong":"6.083330","geolocation":{"type":"Point","coordinates":[6.08333,50.775]}}
,{"name":"Aarhus","id":"2","nametype":"Valid","recclass":"H6","mass":"720","fall":"Fell","year":"1951-01-01T00:00:00.000","reclat":"56.183330","reclong":"10.233330","geolocation":{"type":"Point","coordinates":[10.23333,56.18333]}}
,{"name":"Abee","id":"6","nametype":"Valid","recclass":"EH4","mass":"107000","fall":"Fell","year":"1952-01-01T00:00:00.000","reclat":"54.216670","reclong":"-113.000000","geolocation":{"type":"Point","coordinates":[-113,54.21667]}}
,{"name":"Acapulco","id":"10","nametype":"Valid","recclass":"Acapulcoite","mass":"1914","fall":"Fell","year":"1976-01-01T00:00:00.000","reclat":"16.883330","reclong":"-99.900000","geolocation":{"type":"Point","coordinates":[-99.9,16.88333]}}
,{"name":"Achiras","id":"370","nametype":"Valid","recclass":"L6","mass":"780","fall":"Fell","year":"1902-01-01T00:00:00.000","reclat":"-33.166670","reclong":"-64.950000","geolocation":{"type":"Point","coordinates":[-64.95,-33.16667]}}
,{"name":"Adhi Kot","id":"379","nametype":"Valid","recclass":"EH4","mass":"4239","fall":"Fell","year":"1919-01-01T00:00:00.000","reclat":"32.100000","reclong":"71.800000","geolocation":{"type":"Point","coordinates":[71.8,32.1]}}
]
```

## Реализация

Оставим бинарное дерево и его балансировку за скобками - к этому вернемся позже. 

Почему мы не можем так просто получить доступ к конкретной записи в файле json (предполагая, что он огромный)?
Потому, что даже зная его порядковый номер мы не знаем откуда и докуда читать (адреса байтов).
Значит придется выровнять "таблицу" до конкретного количества байт. И сохранять данные в выровненном виде.
Для этого будем использовать бинарную упаковку для `float` и `int`. Строки в php и так являются байтами - тут просто заполним их нулевыми байтами до какого-то фиксированного значения. 
По итогу получим ровную таблицу, и зная размер "строки" (по сути - сумма длин всех типов полей), сможем высчитывать конкретное смещение в файле.
Идея в том, чтобы структурно получить что-то наподобие массива в низкоуровневых языках.

Первое, что нужно сделать - сконфигурировать поля в бд.
Для этого перейти в файл конфигурации и перечислить поля и типы `./config/database.php`
Если не ясно, какое максимальное значение указать для конкретного строкового поля, то можно запустить команду:
```shell
$ php db.php analyze [path to json file]
```
Она выдаст оценку максимальных длин строковых полей:
```
+----------+-----------+
| field    | max chars |
+----------+-----------+
| name     | 8         |
| nametype | 5         |
| recclass | 11        |
+----------+-----------+
```
Для float и int - жестко выставлено 8 байт.

> Для имен вложенных полей json можно использовать dot-нотацию.
> Например `geolocation.coordinates.1` и `geolocation.coordinates.2`,  


После того как поля были сконфигурированы, можно запустить импорт json файла.
```shell
$ php db.php import [path to json file]
```

> Предполагая что json может быть огромным, 
> я добавил для обработки больших файлов пакет `halaxa/json-machine`
> У него свой токенайзер, и он отдает элементы на обработку в генератор прямо из стрима.

Когда импорт будет завершен, мы сможем добавить индекс на поле, по которому будет производиться поиск:

```shell
$ php db.php index [filed name]
```
Можно добавить индексы на несколько полей.

> При индексации, будет построено бинарное дерево. Сначала, я использовал самобалансирующееся дерево.
> Но потом подсчитал количество поворотов нод на большем дата-сете, и решил, что лучше сбалансировать дерево постфактум.
> Применил [DSW-алгоритм](https://en.wikipedia.org/wiki/Day%E2%80%93Stout%E2%80%93Warren_algorithm).

Когда дерево построено, его данные записываются в бинарный индексный файл. Для записи в индекс дерево обходится в прямом порядке - сверху-вниз.
Таким образом, при чтении из индексного файла и обратном построении дерева, не возникнет необходимости в его ребалансировке.

В самом индексе, хранится индексируемое значение и порядковый номер записи.

Найдя значение в дереве и получив номер записи, мы можем считать его из файла. 
Чтобы не перебирать слишком большие файлы (так или иначе, а нам нужно будет сделать хотя бы и фиктивный `fread` до нужного места), еще на этапе импорта файлы делятся на чанки (параметр `chunk_size` в `./config/database.php`)
Нужный файл вычисляется путем целочисленного деления порядкового номера на `chunk_size` смещение внутри файла остатком от деления на номер чанка - соответственно.

Для поиска:
```shell
$ php db.php search [field name] "[search string]"
```

Результат поиска:
```
+----+----------+----------+-------------+------+-------------+----------------+-----------------------------------+
| id | name     | nametype | recclass    | mass | real offset | offset in file | chunk                             |
+----+----------+----------+-------------+------+-------------+----------------+-----------------------------------+
| 10 | Acapulco | Valid    | Acapulcoite | 1914 | 4           | 3              | /resources/database/data/00001.db |
+----+----------+----------+-------------+------+-------------+----------------+-----------------------------------+
Stats:
+------------+-------------+-------------+-------------+----------+------------+
| build hits | build turns | search hits | memory peak | CPU time | clock time |
+------------+-------------+-------------+-------------+----------+------------+
| 8          | 0           | 3           | 3.18 MB     | 15ms     | 0m 0s      |
+------------+-------------+-------------+-------------+----------+------------+
```

Для сравнения с перебором по файлу:
```shell
$ php db.php bruteforce [file name] [field name] "[search string]"
```
Результат перебора:

```
+----+----------+----------+-------------+------+
| id | name     | nametype | recclass    | mass |
+----+----------+----------+-------------+------+
| 10 | Acapulco | Valid    | Acapulcoite | 1914 |
+----+----------+----------+-------------+------+
Stats:
+-------------+----------+------------+
| memory peak | CPU time | clock time |
+-------------+----------+------------+
| 3.29 MB     | 0ms      | 0m 0s      |
+-------------+----------+------------+
```

Для вызова подсказок к любой команде: вызовите ее с флагом `-h`

## Замеры:

[Датасет](https://data.nasa.gov/api/id/gh4g-9sfh.json?%24select=%60name%60,%60id%60,%60nametype%60,%60recclass%60,%60mass%60,%60fall%60,%60year%60,%60reclat%60,%60reclong%60,%60geolocation%60&%24order=%60:id%60+ASC&%24limit=46000&%24offset=0)

Поиск по индексу:
```
$ php db.php search name "Miller Range 05099"                                                                                                                                                                                                                
Search result:
+-------+--------------------+----------+----------+------+-------------+----------------+-----------------------------------+
| id    | name               | nametype | recclass | mass | real offset | offset in file | chunk                             |                                  |
+-------+--------------------+----------+----------+------+-------------+----------------+-----------------------------------+
| 44494 | Miller Range 05098 | Valid    | H4       | 9.9  | 24333       | 757            | /resources/database/data/00024.db |
+-------+--------------------+----------+----------+------+-------------+----------------+-----------------------------------+
Stats:
+------------+-------------+-------------+-------------+----------+------------+
| build hits | build turns | search hits | memory peak | CPU time | clock time |
+------------+-------------+-------------+-------------+----------+------------+
| 620222     | 0           | 14          | 13.70 MB    | 563ms    | 0m 0s      |
+------------+-------------+-------------+-------------+----------+------------+
```

Перебор:
```
$ php db.php bruteforce "https://data.nasa.gov/api/id/gh4g-9sfh.json?%24select=%60name%60,%60id%60,%60nametype%60,%60recclass%60,%60mass%60,%60fall%60,%60year%60,%60reclat%60,%60reclong%60,%60geolocation%60&%24order=%60:id%60+ASC&%24limit=46000&%24offset=0" name "Miller Range 05099"
Search results:
+-------+--------------------+----------+----------+------+
| id    | name               | nametype | recclass | mass |
+-------+--------------------+----------+----------+------+
| 44495 | Miller Range 05099 | Valid    | L5       | 49.9 |
+-------+--------------------+----------+----------+------+
Stats:                                                     
+-------------+----------+------------+                    
| memory peak | CPU time | clock time |                    
+-------------+----------+------------+                    
| 3.29 MB     | 5469ms   | 0m 12s     |                    
+-------------+----------+------------+ 
```

Здесь следует обратить внимание именно на `CPU time`.

## Недостатки
Очевидно, что файл бд больше, чем json.
Это связано с двумя причинами:
1. Выравнивание строковых полей нулевыми байтами. Все строки имеют длину наибольшего значения из дата-сета.
2. Небольшие инты в строковом представлении могут кодироваться небольшим количеством байт. В бинарном представлении - это всегда 8 (64bit)

## Что можно улучшить
1. Для индекса строковых полей можно использовать не сами значения, а хеш фиксированного размера.
Это позволит не раздувать индекс при больших строковых полях. С другой стороны это может приводить к коллизиям. 
Однако бакеты для значений уже реализованы в дереве так что... можно сделать перебор по коллизиям - это не страшно.
Для хеша можно использовать murmurhash3 - по последним тестам у него самое гладкое распределение и хорошая скорость.
2. Можно попробовать хранить индекс в виде сериализованного объекта PHP. Есть предположение, что это может работать быстрее, чем собирать дерево из бинарного файла.
3. Эта задача не для PHP

## Другие идеи

Вместо того, чтобы городить таблицы, можно было бы взять токенайзер json и отслеживать позицию токенов в файле.
Таким образом можно было бы обойтись без бд. В этом случае, смещение и было бы позицией токена... кажется так.








