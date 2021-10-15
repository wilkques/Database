# DB

## Notice

1. 僅為了 `MySQL` 設計
1. 此為簡易 `DB` 操作
1. 暫無 `join` 功能，但可以利用 method `query` 自己寫 SQL Query

## ENV

1. php >= 5.4
1. mysql >= 5.6
1. PDO extension

## Methods

```php
$drive = new \Wilkques\DB\PDO\MySql('<database host>', '<database username>', '<database password>', 'database name>');

$grammar = new \Wilkques\DB\Grammar\MySql;

$db = \Wilkques\DB\DB::connection($drive)->grammar($grammar);

$model = $db->table('<table name>');
```

1. `select`
    ```php

    $model->select(['<columnName1>', '<columnName2>', '<columnName3>']);

    // or

    $model->select("`<columnName1>`, `<columnName2>`, `<columnName3>`");
    ```
1. `where`
    ```php

    $model->where([
        ['<columnName1>', "=", '<columnValue1>'],
    ]);

    // or

    $model->where('<columnName1>', "=", '<columnValue1>');
    ```
1. `whereIn`
    ```php

    $model->where('<columnName1>', [
        ['<columnValue1>', '<columnValue2>'],
    ]);
    ```
1. `limit`
    ```php

    $model->limit(1); // set query LIMIT
    ```
1. `offset`
    ```php

    $model->offset(1); // set query OFFSET
    ```
1. `groupBy`
    ```php

    $model->groupBy('<columnName1>');
    ```
1. `orderBy`
    ```php

    $model->orderBy('<columnName1>', "DESC"); // default ASC
    ```
1. `get`
    ```php

    $model->get(); // get all data
    ```
1. `first`
    ```php

    $model->first(); // get data
    ```
1. `update`
    ```php

    $model->where('<columnName1>', "=", '<columnValue1>')
        ->update([
            '<updateColumnName1>' => '<updateColumnValue1>'
        ]);
    ```

### 可自寫 SQL Query
1. `query` set SQL string
    ```php

    $model->query("<SQL String>")->exec();

    // for example

    $model->query("SELECT * FROM `<your table name>`")->exec();
    ```
1. `exec` execute SQL string
    ```php

    $model->exec();
    ```
1. `bindData` bind query data
    ```php

    $model->bindData([
        '<value1>', '<value2>' ...
    ])->exec();

    // or

    $model->bindData(
        '<value1>', '<value2>' ...
    )->exec();

    // for example

    $model->query("SELECT * FROM `<your table name>` WHERE `<columnName1>` = ? AND `columnName2` = ?")
        ->bindData(['<columnValue1>', '<columnValue2>'])
        ->exec();
    ```

### 取得 Query Log
1. `getQueryLog` get all query string and bind data
    ```php

    $db->getQueryLog();
    ```
1. `latestQueryLog` get latest query string and bind data
    ```php

    $db->latestQueryLog();
    ```

### 鎖
1. `lockForUpdate`
    ```php
    
    $db->lockForUpdate();
    ```
1. `sharedLock`
    ```php
    
    $db->sharedLock();
    ```

### 分頁
1. `currentPage`
    ```php

    $model->currentPage(); // now page
    ```
1. `prePage`
    ```php

    $model->prePage(); // pre page
    ```
1. `getForPage`
    ```php

    $model->getForPage(); // get page data
    ```

### 交易模式
1. `beginTransaction`
    ```php
    
    $db->beginTransaction();
    ```
1. `commit`
    ```php
    
    $db->commit();
    ```
1. `rollback`
    ```php
    
    $db->rollback();
    ```

## connect

1. `host`
    ```php

    $db->host('<DB host>');
    ```
1. `username`
    ```php

    $db->username('<DB username>');
    ```
1. `password`
    ```php

    $db->password('<DB password>');
    ```
1. `dbname`
    ```php

    $db->dbname('<DB name>');
    ```
1. `connect`
    ```php

    $db->newConnect();
    ```