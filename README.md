# ZDB

###### Библиотека  для  доступа   к БД

Библиотека  представляет  собой, Active Record построеный 
на  функциональности  библиотекаи [ADODB](http://adodb.org/dokuwiki/doku.php).

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

Предварительно  нужно  указать параметры  конекта к ADODB

      DB::config($host, $dbname, $user, $pass,$driver);

 Детально  в [документации](https://github.com/leon-mbs/zdb/blob/master/docs.md) и в [описании проекта](http://zippy.com.ua/zdb) 
