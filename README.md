# ZDB

###### Библиотека  для  доступа   к БД

Библиотека  представляет  собой, Active Record построеный 
на  функциональности  библиотеки [ADODB](https://github.com/ADOdb/ADOdb).

Домашняя страница:  [https://zippy.com.ua/zdb](https://zippy.com.ua/zdb)


Пример использования

    CREATE TABLE   `users` (
      `username` varchar(255) ,
      `created` date  ,
      `user_id` int(11) NOT NULL AUTO_INCREMENT,
      PRIMARY KEY (`user_id`)
    )
    
Создадим класс сущности Пользователь, на основе класса Entity

    /**
     * @table=users
     * @keyfield=user_id
     */
    class User extends Entity{
    
    }
Собственно и все.
Используется просто:

        $user = new User();
        $user->username='Вася Пупкин';
        $user->created=time();
        $user->save(); //сохраняем в  хранилище
 
 загрузим  опять
 
        $thesameuser = User::load($user->user_id);
        echo $thesameuser ->username;

Предварительно  нужно  указать параметры  соединения к БД

      DB::config($host, $dbname, $user, $pass,$driver);

####   Конфигурирование  

Конфигурация  Entity осуществляется с  помощью  псевдоанотаций.

@table - имя  таблицы в  БД  
@keyfield - имя поля первичного ключа  
@view - если  существует  представление для  выборки сущности, тогда  выборка  будет осуществлятся через представление  а  не таблицу   
Альтернативный вариант - переопределить  метод getMetadata()

####   основные функции

Описание  и параметры всех функций - в  исходном  коде.

***Save()***  
Записывает содержимое в таблицу. Если  ключевое поле  равно 0 создается  новая  запись и ключевому  полю сущности  присвивается  уникальное значение  
В свойствах сущности типа Дата рекомендуется  использовать timestamp - в  этом  случае  фреймворк  сам  сформирует  дату  в соответстви  с  выбранным  типом  БД.

***load($id)***  
Загружает  экземпляр  сущности  по  ключу


***delete($id)***   
Удаление  сущности из  БД


***find($where = '', $orderbyfield = null,   $count = -1, $offset = -1)***    
Поиск по  условию. Условие -выражение для  SQL.  
Возвращает массив  сущностей.  
Для безопасности  строковые параметры   можно  обработать  функцией Entyty::qstr()  
Дата  форматируется с  помощью Entity::dbdate($timestamp)

     User::find("createddate > " . Entity::dbdate(time()-24*3600));
    
    
***findCnt($where = '')***   
Возвращает количество  по  условию

***findArray($fieldname, $where = '', $orderbyfield = null,   $count = -1, $offset = -1)***   
Возвращает массив ключ значение для  указанного  поля  по  условию.  
Использует прежде  всего  для  выпадающих списков.

    User::findArray('username');
    
***findBySql($sql)***  
Выборка  с произвольным запросом если  нужны группировки , связи и т.д.  
В  списке  полей   должно быть  ключевое  поле  для  сущности, являющейся  базовой в  выборке.

    User::findBySql('SELECT user_id,username,count(sessionid) as scount FROM users join sessions on users.user_id = sessions.user_id  group by  user_id,username');  

####   События жизненного цикла  
Для использования  необходимо  переопределить соотвующий метод родительского класса  

***init()***  
вызывается  при создании экземпляра каласса сущности
Используется  для  инициализации полей.

    protected function init()
    {
        $this->user_id = 0;
        $this->createddate = time();
    }

***afterLoad()***  
Вызывается  после  загрузки сущности  из  БД например для  преобразования стройной даты из БД  в  
более  удобный timestamp 

    protected function afterLoad()
    {
        $this->createddate = strtotime($this->createddate);
    }


***beforeSave()***  
Может использоватся  для  валидации перед сохранением 

***beforeDelete()***  
Может использоватся  для  валидации или очистки  связанных ресурсов (картинок например)
