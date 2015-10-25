<?php



require_once __DIR__.'/../../../vendor/autoload.php';
require_once __DIR__.'/../../../src/app.php';

use Symfony\Component\Validator\Constraints as Assert;

$app->match('/staff/users/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {  
    $start = 0;
    $vars = $request->query->all();
    $qsStart = (int)$vars["start"];
    $search = $vars["search"];
    $order = $vars["order"];
    $columns = $vars["columns"];
    $qsLength = (int)$vars["length"];    
    
    if($qsStart) {
        $start = $qsStart;
    }    
    
    $index = $start;   
    $rowsPerPage = $qsLength;
       
    $rows = array();
    
    $searchValue = $search['value'];
    $orderValue = $order[0];
    
    $orderClause = "";
    if($orderValue) {
        $orderClause = " ORDER BY ". $columns[(int)$orderValue['column']]['data'] . " " . $orderValue['dir'];
    }
    
    $table_columns = array(
        'UserId', 
        'UserFirstName', 
        'UserLastName', 
        'UsersBirthDate', 
        'UserAddress', 
        'UserAddressN', 
        'UserAddressB', 
        'UserAddressC', 
        'UserAddressL', 
        'UserPhone', 
        'UserMail', 
        'Userpassword', 
        'UserCreationDate', 
        'RoleId', 

    );
    
    $table_columns_type = array(
        'int(11)', 
        'varchar(45)', 
        'varchar(45)', 
        'date', 
        'varchar(45)', 
        'int(11)', 
        'int(11)', 
        'int(11)', 
        'varchar(45)', 
        'varchar(45)', 
        'varchar(45)', 
        'longtext', 
        'timestamp', 
        'int(11)', 

    );    
    
    $whereClause = "";
    
    $i = 0;
    foreach($table_columns as $col){
        
        if ($i == 0) {
           $whereClause = " WHERE";
        }
        
        if ($i > 0) {
            $whereClause =  $whereClause . " OR"; 
        }
        
        $whereClause =  $whereClause . " " . $col . " LIKE '%". $searchValue ."%'";
        
        $i = $i + 1;
    }
    
    $recordsTotal = $app['db']->executeQuery("SELECT * FROM `users`" . $whereClause . $orderClause)->rowCount();
    
    $find_sql = "SELECT * FROM `users`". $whereClause . $orderClause . " LIMIT ". $index . "," . $rowsPerPage;
    $rows_sql = $app['db']->fetchAll($find_sql, array());

    foreach($rows_sql as $row_key => $row_sql){
        for($i = 0; $i < count($table_columns); $i++){

        if( $table_columns_type[$i] != "blob") {
                $rows[$row_key][$table_columns[$i]] = $row_sql[$table_columns[$i]];
        } else {                if( !$row_sql[$table_columns[$i]] ) {
                        $rows[$row_key][$table_columns[$i]] = "0 Kb.";
                } else {
                        $rows[$row_key][$table_columns[$i]] = " <a target='__blank' href='menu/download?id=" . $row_sql[$table_columns[0]];
                        $rows[$row_key][$table_columns[$i]] .= "&fldname=" . $table_columns[$i];
                        $rows[$row_key][$table_columns[$i]] .= "&idfld=" . $table_columns[0];
                        $rows[$row_key][$table_columns[$i]] .= "'>";
                        $rows[$row_key][$table_columns[$i]] .= number_format(strlen($row_sql[$table_columns[$i]]) / 1024, 2) . " Kb.";
                        $rows[$row_key][$table_columns[$i]] .= "</a>";
                }
        }

        }
    }    
    
    $queryData = new queryData();
    $queryData->start = $start;
    $queryData->recordsTotal = $recordsTotal;
    $queryData->recordsFiltered = $recordsTotal;
    $queryData->data = $rows;
        
    return new Symfony\Component\HttpFoundation\Response(json_encode($queryData), 200);
});




/* Download blob img */
$app->match('/staff/users/download', function (Symfony\Component\HttpFoundation\Request $request) use ($app) { 
    
    // menu
    $rowid = $request->get('id');
    $idfldname = $request->get('idfld');
    $fieldname = $request->get('fldname');
    
    if( !$rowid || !$fieldname ) die("Invalid data");
    
    $find_sql = "SELECT " . $fieldname . " FROM " . users . " WHERE ".$idfldname." = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($rowid));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('menu_list'));
    }

    header('Content-Description: File Transfer');
    header('Content-Type: image/jpeg');
    header("Content-length: ".strlen( $row_sql[$fieldname] ));
    header('Expires: 0');
    header('Cache-Control: public');
    header('Pragma: public');
    ob_clean();    
    echo $row_sql[$fieldname];
    exit();
   
    
});



$app->match('/staff/users', function () use ($app) {
    
    $table_columns = array(
        
        'UserFirstName', 
        'UserLastName', 
        'UsersBirthDate',  
        'UserAddressC', 
        'UserAddressL', 
        'UserPhone', 
        'UserMail', 
        

    );
     $table_columns_labels = array(
        
        'Prénom', 
        'Nom', 
        'Date de naissance', 
        'Code postal', 
        'Localité', 
        'Numéro de téléphone', 
        'E-mail', 
        

    );

    $primary_key = "UserId";    

    return $app['twig']->render('users/list.html.twig', array(
        "table_columns" => $table_columns,
        "table_columns_labels" => $table_columns_labels,
        "primary_key" => $primary_key,
    ));
        
})
->bind('users_list');



$app->match('/staff/users/create', function () use ($app) {
    
    $initial_data = array(
        'UserFirstName' => '', 
        'UserLastName' => '', 
        'UsersBirthDate' => '', 
        'UserAddress' => '', 
        'UserAddressN' => '', 
        'UserAddressB' => '', 
        'UserAddressC' => '', 
        'UserAddressL' => '', 
        'UserPhone' => '', 
        'UserMail' => '', 
        

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);



    $form = $form->add('UserFirstName', 'text', array('required' => true));
    $form = $form->add('UserLastName', 'text', array('required' => true));
    $form = $form->add('UsersBirthDate', 'text', array('required' => false));
    $form = $form->add('UserAddress', 'text', array('required' => false));
    $form = $form->add('UserAddressN', 'text', array('required' => false));
    $form = $form->add('UserAddressB', 'text', array('required' => false));
    $form = $form->add('UserAddressC', 'text', array('required' => false));
    $form = $form->add('UserAddressL', 'text', array('required' => false));
    $form = $form->add('UserPhone', 'text', array('required' => false));
    $form = $form->add('UserMail', 'text', array('required' => false));
   


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "INSERT INTO `users` (`UserFirstName`, `UserLastName`, `UsersBirthDate`, `UserAddress`, `UserAddressN`, `UserAddressB`, `UserAddressC`, `UserAddressL`, `UserPhone`, `UserMail`, `Userpassword`, `UserCreationDate`, `RoleId`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $app['db']->executeUpdate($update_query, array($data['UserFirstName'], $data['UserLastName'], $data['UsersBirthDate'], $data['UserAddress'], $data['UserAddressN'], $data['UserAddressB'], $data['UserAddressC'], $data['UserAddressL'], $data['UserPhone'], $data['UserMail'], $data['Userpassword'], $data['UserCreationDate'], 0));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'users created!',
                )
            );
            return $app->redirect($app['url_generator']->generate('users_list'));

        }
    }

    return $app['twig']->render('users/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('users_create');



$app->match('/staff/users/edit/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `users` WHERE `UserId` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('users_list'));
    }

    
    $initial_data = array(
        'UserFirstName' => $row_sql['UserFirstName'], 
        'UserLastName' => $row_sql['UserLastName'], 
        'UsersBirthDate' => $row_sql['UsersBirthDate'], 
        'UserAddress' => $row_sql['UserAddress'], 
        'UserAddressN' => $row_sql['UserAddressN'], 
        'UserAddressB' => $row_sql['UserAddressB'], 
        'UserAddressC' => $row_sql['UserAddressC'], 
        'UserAddressL' => $row_sql['UserAddressL'], 
        'UserPhone' => $row_sql['UserPhone'], 
        'UserMail' => $row_sql['UserMail'], 
        

    );


    $form = $app['form.factory']->createBuilder('form', $initial_data);


    $form = $form->add('UserFirstName', 'text', array('required' => true));
    $form = $form->add('UserLastName', 'text', array('required' => true));
    $form = $form->add('UsersBirthDate', 'text', array('required' => false));
    $form = $form->add('UserAddress', 'text', array('required' => false));
    $form = $form->add('UserAddressN', 'text', array('required' => false));
    $form = $form->add('UserAddressB', 'text', array('required' => false));
    $form = $form->add('UserAddressC', 'text', array('required' => false));
    $form = $form->add('UserAddressL', 'text', array('required' => false));
    $form = $form->add('UserPhone', 'text', array('required' => false));
    $form = $form->add('UserMail', 'text', array('required' => false));
    
    


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "UPDATE `users` SET `UserFirstName` = ?, `UserLastName` = ?, `UsersBirthDate` = ?, `UserAddress` = ?, `UserAddressN` = ?, `UserAddressB` = ?, `UserAddressC` = ?, `UserAddressL` = ?, `UserPhone` = ?, `UserMail` = ?, `Userpassword` = ?, `RoleId` = ? WHERE `UserId` = ?";
            $app['db']->executeUpdate($update_query, array($data['UserFirstName'], $data['UserLastName'], $data['UsersBirthDate'], $data['UserAddress'], $data['UserAddressN'], $data['UserAddressB'], $data['UserAddressC'], $data['UserAddressL'], $data['UserPhone'], $data['UserMail'], $data['Userpassword'], 0, $id));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'users edited!',
                )
            );
            return $app->redirect($app['url_generator']->generate('users_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('users/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('users_edit');



$app->match('/staff/users/delete/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `users` WHERE `UserId` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "DELETE FROM `users` WHERE `UserId` = ?";
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => 'users deleted!',
            )
        );
    }
    else{
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );  
    }

    return $app->redirect($app['url_generator']->generate('users_list'));

})
->bind('users_delete');






$app->match('/', function () use ($app) {
    
    

    $form = $app['form.factory']->createBuilder('form', $initial_data);



    $form = $form->add('UserMail', 'text', array('required' => true));
    $form = $form->add('Userpassword', 'password', array('required' => true));
  
    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

             $find_sql = "SELECT * FROM `users` WHERE (`UserMail` = UserMail AND `Userpassword` = Userpassword)";
             $row_sql = $app['db']->fetchAssoc($find_sql, array($id));


            if(!$row_sql){
                     $app['session']->getFlashBag()->add(
                         'danger',
                     array(
                          'message' => 'wroooooooooooooong!',
                          )
                       );        
                     
         return $app->redirect($app['url_generator']->generate('users_login'));
                    }


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'connected!'.var_dump($row_sql),
                )
            );
            return $app->redirect($app['url_generator']->generate('users_login'));

        }
    }

    return $app['twig']->render('index.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('index');


$app->match('/users/login', function () use ($app) {

  return $app['twig']->render('users/login.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('users_login');
