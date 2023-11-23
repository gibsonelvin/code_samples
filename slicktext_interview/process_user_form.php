<?php
require('./autoload.php');
$action = $_GET['action'];

if(in_array($action, ['create', 'update'])) {
    $user = new Users();
    $user->load($_POST);
    $user->save();
    echo json_encode(['data' => $user->dumpProperties()]);

} else if($action === 'delete') {
    $user = new Users();
    $user->loadById($_GET['id']);
    $user->delete();
    echo "success";
    
} else {
    AppError::reportError("Invalid action specified: " . $action);
}