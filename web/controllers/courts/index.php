<?php



require_once __DIR__.'/../../../vendor/autoload.php';
require_once __DIR__.'/../../../src/app.php';

use Symfony\Component\Validator\Constraints as Assert;

$app->match('/staff/courts/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {  
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
		'CourtId', 
		'CourtName', 
		'CourtZone', 
		'CourtAddress', 
		'CourtSurface', 
		'CourtAvailability', 
		'CourtOwner', 

    );
    
    $table_columns_type = array(
		'int(11)', 
		'varchar(45)', 
		'varchar(45)', 
		'varchar(45)', 
		'float', 
		'int(11)', 
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
    
    $recordsTotal = $app['db']->executeQuery("SELECT * FROM `courts`" . $whereClause . $orderClause)->rowCount();
    
    $find_sql = "SELECT * FROM `courts`". $whereClause . $orderClause . " LIMIT ". $index . "," . $rowsPerPage;
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
$app->match('/staff/courts/download', function (Symfony\Component\HttpFoundation\Request $request) use ($app) { 
    
    // menu
    $rowid = $request->get('id');
    $idfldname = $request->get('idfld');
    $fieldname = $request->get('fldname');
    
    if( !$rowid || !$fieldname ) die("Invalid data");
    
    $find_sql = "SELECT " . $fieldname . " FROM " . courts . " WHERE ".$idfldname." = ?";
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



$app->match('/staff/courts', function () use ($app) {
    
	$table_columns = array( 
		'CourtName', 
		'CourtZone', 
		'CourtAddress', 
		'CourtSurface', 
		'CourtAvailability', 
		'CourtOwner', 

    );
    $table_columns_labels = array( 
        'Nom du court', 
        'Zone', 
        'Adresse', 
        'Surface', 
        'Disponibilité', 
        'Propriétaire',
    );

    $primary_key = "CourtId";	

    return $app['twig']->render('courts/list.html.twig', array(
        "table_columns" => $table_columns,
    	"table_columns_labels" => $table_columns_labels,
        "primary_key" => $primary_key
    ));
        
})
->bind('courts_list');



$app->match('/staff/courts/create', function () use ($app) {
    
    $initial_data = array(
		'CourtName' => '', 
		'CourtZone' => '', 
		'CourtAddress' => '', 
		'CourtSurface' => '', 
		'CourtAvailability' => '', 
		'CourtOwner' => '', 

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);



	$form = $form->add('CourtName', 'text', array('required' => false));
	$form = $form->add('CourtZone', 'text', array('required' => false));
	$form = $form->add('CourtAddress', 'text', array('required' => false));
	$form = $form->add('CourtSurface', 'text', array('required' => false));
	$form = $form->add('CourtAvailability', 'text', array('required' => false));
	$form = $form->add('CourtOwner', 'text', array('required' => false));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "INSERT INTO `courts` (`CourtName`, `CourtZone`, `CourtAddress`, `CourtSurface`, `CourtAvailability`, `CourtOwner`) VALUES (?, ?, ?, ?, ?, ?)";
            $app['db']->executeUpdate($update_query, array($data['CourtName'], $data['CourtZone'], $data['CourtAddress'], $data['CourtSurface'], $data['CourtAvailability'], $data['CourtOwner']));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'courts created!',
                )
            );
            return $app->redirect($app['url_generator']->generate('courts_list'));

        }
    }

    return $app['twig']->render('courts/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('courts_create');



$app->match('/staff/courts/edit/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `courts` WHERE `CourtId` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('courts_list'));
    }

    
    $initial_data = array(
		'CourtName' => $row_sql['CourtName'], 
		'CourtZone' => $row_sql['CourtZone'], 
		'CourtAddress' => $row_sql['CourtAddress'], 
		'CourtSurface' => $row_sql['CourtSurface'], 
		'CourtAvailability' => $row_sql['CourtAvailability'], 
		'CourtOwner' => $row_sql['CourtOwner'], 

    );


    $form = $app['form.factory']->createBuilder('form', $initial_data);


	$form = $form->add('CourtName', 'text', array('required' => false));
	$form = $form->add('CourtZone', 'text', array('required' => false));
	$form = $form->add('CourtAddress', 'text', array('required' => false));
	$form = $form->add('CourtSurface', 'text', array('required' => false));
	$form = $form->add('CourtAvailability', 'text', array('required' => false));
	$form = $form->add('CourtOwner', 'text', array('required' => false));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "UPDATE `courts` SET `CourtName` = ?, `CourtZone` = ?, `CourtAddress` = ?, `CourtSurface` = ?, `CourtAvailability` = ?, `CourtOwner` = ? WHERE `CourtId` = ?";
            $app['db']->executeUpdate($update_query, array($data['CourtName'], $data['CourtZone'], $data['CourtAddress'], $data['CourtSurface'], $data['CourtAvailability'], $data['CourtOwner'], $id));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'courts edited!',
                )
            );
            return $app->redirect($app['url_generator']->generate('courts_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('courts/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('courts_edit');



$app->match('/staff/courts/delete/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `courts` WHERE `CourtId` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "DELETE FROM `courts` WHERE `CourtId` = ?";
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => 'courts deleted!',
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

    return $app->redirect($app['url_generator']->generate('courts_list'));

})
->bind('courts_delete');






