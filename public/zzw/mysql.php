<?php
    /* 这种方式已经废弃了
    function by_mysql($mysql_config)
    {
        $link = @mysql_connect($mysql_config['host'], $mysql_config['username'], $mysql_config['password']);
        if (!$link)
        {
            die("could not connect to mysql:" . mysql_error());
        }
        mysql_query("set names 'utf8'");
        $result = mysql_select_db($mysql_config['database']);
        if (!$result)
        {
            die("could not use database:" . mysql_error());
        }
        $sql = "SELECT * FROM users WHERE id_crc64 = 1980955020;";
        $result  = mysql_query($sql);
        if (!$result)
        {
            die("invalid query:" . mysql_error());
        }
        if (mysql_num_rows($result) == 0)
        {
            die("no rows found, nothing to print so am exiting");
        }
        while ($row = mysql_fetch_assoc($result))
        {
            print_r($row);
        }
        mysql_close($link);
    }
    */


    function by_mysqli($mysql_config)
    {
        $mysqli = @new mysqli($mysql_config['host'], $mysql_config['username'], $mysql_config['password']);
        if ($mysqli->connect_errno)
        {
            die("could not connect to mysql:" . $mysqli->connect_error);
        }
        $mysqli->query("set names 'utf8';");
        $result = $mysqli->select_db($mysql_config['database']);
        if (!$result)
        {
            die("could not use database:" .  $mysqli->error);
        }
        $sql = "SELECT * FROM users WHERE id_crc64 = 1980955020;";
        $result = $mysqli->query($sql);
        if (!$result)
        {
            die("invalid query:\n" . $mysqli->error);
        }
        if (mysqli_num_rows($result) == 0)
        {
            die("no rows found, nothing to print so am exiting");
        }
        while ($row = $result->fetch_assoc())
        {
            print_r($row);
        }
        $result->free();
        $mysqli->close();
    }


    function by_pdo($mysql_config)
    {
        $pdo = new PDO("mysql:host=" . $mysql_config['host'] . ";dbname=" . $mysql_config['database'], $mysql_config['username'], $mysql_config['password']);
        $pdo->exec("set names 'utf8'");
        $sql = "SELECT * FROM users WHERE id_crc64 = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(1, 1980955020, PDO::PARAM_INT);
        //$sth->bindValue(1, "name", PDO::PARAM_STR);
        $result = $stmt->execute();
        if ($result)
        {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC))
            {
                print_r($row);
            }
        }
        $pdo = null;
    }


    $mysql_config = array(
        'host'     => '127.0.0.1:3306',
        'database' => 'laravelapp',
        'username' => 'root',
        'password' => '123456',
    );
    by_pdo($mysql_config);
?>\