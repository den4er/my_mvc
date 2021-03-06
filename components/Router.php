<?php

/*
 *
 * Класс получает массив с маршрутами, получает URI
 * Сравнивает ключи массива маршрутов и запрос в URI
 * Если есть совпадение, берет из значения элемента массива имя класса и метода
 * Подключает нужный класс, создает экземпляр  вызывает метод
 *
 * */
class Router
{
    private $routes; // массив с маршрутами

    public function __construct()   // при создании объекта заполняем свойство $routes маршрутами
    {
        $routesPath = ROOT.'/config/routes.php';    // получаем путь до файла с имеющимися маршрутами
        $this->routes = include($routesPath);   // файл возвращает массив, поэтому подключая файл мы записываем его в свойство $routes
    }

    /*
     *
     *   метод для получения строки запроса из браузера
     *   метод приватный, так как нужен только для внутренних целей, получения строки запроса
     *   вызывается в методе run
     *
    */
    private function getURI()
    {
        if( !empty($_SERVER['REQUEST_URI']) ) // если в строке запроса не пусто
        {
            return trim( $_SERVER['REQUEST_URI'], '/' );    // возвращаем ее обрезая /
        }
    }


    /*
     *
     *  функция получает данные из URI,
     *  анализирует данные,
     *  подключает соответствующий класс
     *  и вызывает соответствующий метод
     *
     */
    public function run()
    {
        // вызываем описаный выше метод и получаем строку запроса
        $uri = $this->getURI();

        // перебираем в цикле все маршруты, которые есть в файле маршрутов
        foreach ( $this->routes as $uriPattern => $path )
        {
            /*
             *
             *  сравним данные из строки запроса с данными в имеющихся маршрутах
             *  $uriPattern - это ключ в массиве с маршрутами
             *  $uri - это то, что мы получили из адресной строки
             *
             *  если в строке введен адрес, который есть в списке маршрутов
             *  нужно определить соответствующий класс и метод
             *
             * */
            if( preg_match( "~^$uriPattern$~", $uri ) )
            {

//                echo "<br>Запрос, который набрал пользователь: $uri ";      // news/sport/12
//                echo "<br>Ищем по шаблону: $uriPattern "; // news/([a-z]+)/([0-9]+)
//                echo "<br>Куда подставляем: $path ";                        // news/view/s1/s2

                // получаем внутренний путь из внешнего согласно правилу
                $internalRoute = preg_replace( "~$uriPattern~", $path, $uri);

//                echo "<hr> Нужно сформировать: $internalRoute<br>";


                // разделяем значение элемента массива с маршрутами (строку) на части по слешу / , получаем массив $segments
                $segments = explode('/', $internalRoute);

                /*
                 *
                 *  В первую очередь получаем название файла класса контролера, который будет обрабатывать запрос из uri
                 *
                 */

                // отрезаем первый элемент массива, тот что был в значении элемента массива с маршрутами до слеша /
                // и кладем его в переменную, получается имя контроллера
                $controllerName = array_shift($segments);

                // делаем первую букву большой и приписываем в конце строку 'Controller', таким образом получаем название файла Класса, который обрабатывает запрос
                $controllerName = ucfirst($controllerName) . 'Controller';



                /*
                 *
                 *  Теперь получаем имя метода в контроллере, который будет вызываться для обработки запроса
                 *
                 */

                // откусываем еще один элемент от массива с маршрутом, делаем первую букву заглавной
                // и приписываем 'action' в начале, т.о. получаем название метода, который нужно вызвать
                $actionName = 'action' . ucfirst( array_shift($segments ) );

                // берем оставшиеся данные и кладем в массив с параметрами
                $parameters = $segments;


                /*
                 *
                 * подключим нужный файл класса контроллера
                 *
                 */
                // получим имя файла контроллера вместе с путем до него
                $controllerFile = ROOT . '/controllers/' . $controllerName . '.php';

                // если файл есть, подключаем его
                if( file_exists( $controllerFile ) )
                {
                    include_once( $controllerFile );
                }

                /*
                 *
                 * создадим объект и вызовем нужный метод
                 *
                 */
                $controllerObject = new $controllerName;
                $result = $controllerObject->$actionName($parameters);


                // если метод не вернул NULL
                if( $result != null )
                {
                    // прерываем цикл
                    break;
                }
            }
        }
    }
}