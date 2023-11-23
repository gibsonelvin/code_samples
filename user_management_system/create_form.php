<?php
$localeData = json_decode(file_get_contents("localeData.json"));
?>

<h1 id='message'>New User</h1>
<div onclick='Main.closeModal()' class='close_button flink'>x</div>

<div id='form_error'></div>
<form id='user_entry'>

    <div id='form_error'>&nbsp;</div>

    <div class='form_label'>
        <label>First Name:</label>
    </div>
    <div class='form_field'>
        <input type='text' id='first_name' name='first_name' size=16 />
    </div>

    <div class='form_label'>
    <label>Last Name:</label>
    </div>
    <div class='form_field'>
    <input type='text' id='last_name' name='last_name' size=16 />
    </div>
    
    <div class='form_label'>
    <label>Email:</label>
    </div>
    <div class='form_field'>
    <input type='text' id='email' name='email' size=32 />
    </div>
    
    <div class='form_label'>
    <label>Mobile Number:</label>
    </div>
    <div class='form_field'>
    <input type='text' id='mobile_number' name='mobile_number' maxlength=10 size=32 />
    </div>
    
    <div class='form_label'>
    <label>Address:</label>
    </div>
    <div class='form_field'>
    <input type='text' id='address' name='address' size=32 />
    </div>
    
    <div class='form_label'>
    <label>City:</label>
    </div>
    <div class='form_field'>
    <input type='text' id='city' name='city' size=32 />
    </div>
    
    <div class='form_label'>
    <label>State:</label>
    </div>
    <div class='form_field' id='state_field_container'>
    <select id='state' name='state'>
    <?php
    // US by default
    foreach($localeData->countries->US as $state) {
        echo "<option value='" . $state->state_name . "'>" . $state->state_name . "</option>";
    }
    ?>
    </select>
    </div>
    
    <div class='form_label'>
    <label>Zip:</label>
    </div>
    <div class='form_field'>
    <input type='text' id='zip' name='zip' maxlength=5 size=32 />
    </div>
    
    <div class='form_label'>
    <label>Country:</label>
    </div>
    <div class='form_field'>
        <select id='country' name='country' onchange='Main.updateStates()'>
            <?php
            foreach($localeData->countries as $country => $states) {
                // US by default
                $selected = ($country === 'US' ? ' selected': '');
                echo "<option value='" . $country . "'" . $selected . ">" . $country . "</option>";
            }
            ?>
        </select>
    </div>
    
    <div class='form_label'>
    <label>Timezone:</label>
    </div>
    <div class='form_field'>
    <input type='text' id='timezone' name='timezone' size=32 />
    </div>

    <div class='form_submit'>
        <input type='button' value='Submit' onClick='Main.submitCreateForm()' />
    </div>




</form>