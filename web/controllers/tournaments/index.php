<?php



require_once __DIR__.'/../../../vendor/autoload.php';
require_once __DIR__.'/../../../src/app.php';

use Symfony\Component\Validator\Constraints as Assert;

$app->match('/staff/tournaments/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {  
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
		'TournamentId', 
		'TournamentName', 

    );
    
    $table_columns_type = array(
		'int(11)', 
		'varchar(45)', 

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
    
    $recordsTotal = $app['db']->executeQuery("SELECT * FROM `tournaments`" . $whereClause . $orderClause)->rowCount();
    
    $find_sql = "SELECT * FROM `tournaments`". $whereClause . $orderClause . " LIMIT ". $index . "," . $rowsPerPage;
    $rows_sql = $app['db']->fetchAll($find_sql, array());

    foreach($rows_sql as $row_key => $row_sql){
        for($i = 0; $i < count($table_columns); $i++){

		if( $table_columns_type[$i] != "blob") {
				$rows[$row_key][$table_columns[$i]] = $row_sql[$table_columns[$i]];
		} else {				if( !$row_sql[$table_columns[$i]] ) {
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
$app->match('/staff/tournaments/download', function (Symfony\Component\HttpFoundation\Request $request) use ($app) { 
    
    // menu
    $rowid = $request->get('id');
    $idfldname = $request->get('idfld');
    $fieldname = $request->get('fldname');
    
    if( !$rowid || !$fieldname ) die("Invalid data");
    
    $find_sql = "SELECT " . $fieldname . " FROM " . tournaments . " WHERE ".$idfldname." = ?";
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



$app->match('/staff/tournaments', function () use ($app) {
    
	$table_columns = array(
		
		'TournamentName', 

    );
    $table_columns_labels = array(
        
        'Nom du tournois', 

    );

    $primary_key = "TournamentId";	

    return $app['twig']->render('tournaments/list.html.twig', array(
        "table_columns" => $table_columns,
    	"table_columns_labels" => $table_columns_labels,
        "primary_key" => $primary_key
    ));
        
})
->bind('tournaments_list');



$app->match('/staff/tournaments/create', function () use ($app) {
    
    $initial_data = array(
		'TournamentName' => '', 

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);



	$form = $form->add('TournamentName', 'text', array('required' => false));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "INSERT INTO `tournaments` (`TournamentName`) VALUES (?)";
            $app['db']->executeUpdate($update_query, array($data['TournamentName']));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'tournaments created!',
                )
            );
            return $app->redirect($app['url_generator']->generate('tournaments_list'));

        }
    }

    return $app['twig']->render('tournaments/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('tournaments_create');



$app->match('/staff/tournaments/edit/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `tournaments` WHERE `TournamentId` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('tournaments_list'));
    }

    
    $initial_data = array(
		'TournamentName' => $row_sql['TournamentName'], 

    );


    $form = $app['form.factory']->createBuilder('form', $initial_data);


	$form = $form->add('TournamentName', 'text', array('required' => false));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "UPDATE `tournaments` SET `TournamentName` = ? WHERE `TournamentId` = ?";
            $app['db']->executeUpdate($update_query, array($data['TournamentName'], $id));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'tournaments edited!',
                )
            );
            return $app->redirect($app['url_generator']->generate('tournaments_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('tournaments/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('tournaments_edit');



$app->match('/staff/tournaments/delete/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `tournaments` WHERE `TournamentId` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "DELETE FROM `tournaments` WHERE `TournamentId` = ?";
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => 'tournaments deleted!',
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

    return $app->redirect($app['url_generator']->generate('tournaments_list'));

})
->bind('tournaments_delete');






