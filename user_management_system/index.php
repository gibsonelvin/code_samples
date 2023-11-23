<?php
require('autoload.php');

$userModel = new Users();
$getUsers = $userModel->getAll();
require('header.php');
?>


<div class='action_modal_back'></div>

<div class='action_modal'>
<!-- <?php require('create_form.php');?> -->
</div>

<div id='msg_modal'>
    <div id='modal_frame'>
        <div id='modal_message'></div>
    </div>
</div>

<div class='body_back'>

<h3>Users <div class='new_button flink' onclick='Main.create()'>+</div></h3>

<center>

<table id='users_table' border=0 cellpadding=5 cellspacing=0>
    <?php


    if($getUsers->num_rows === 0) {
        echo "<h3 id='no_records'>No records to show</h3>";
    } else {
       $i = 0;
        while($userData = $getUsers->fetch_assoc()) {

            if($i === 0) {
                Model::outputHeaderRow($userData);
            }

            echo "<tr id='user_" . $userData['id'] . "'>";
            
            $column_i = 0;
            foreach($userData as $dataBit) {
                echo "<td class='column" . $column_i . "'>";
                echo $dataBit;
                echo "</td>";
                $column_i++;
            }

            echo "<td class='icon flink' onclick='Main.edit(" . json_encode($userData) . ")'>📝</td>"
                . "<td class='icon flink' onclick='Main.delete(" . $userData['id'] . ")'>🗑️</td></tr>";
            $i = 1;
        }
    }

    ?>

</table>
</center>

</div>
<div></div>



<?php
require('footer.php');
?>