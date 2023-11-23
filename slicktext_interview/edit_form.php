<?php
$localeData = json_decode(file_get_contents("localeData.json"));
extract(json_decode($_GET['data'], true));
?>

<h1 id='message'>Edit User</h1>
<div onclick='Main.closeModal()' class='close_button flink'>x</div>

<div id='form_error'></div>
<form id='user_entry'>

<!-- WITH THE REDUNDANCIES OF THESE FIELDS, IF TIME PERMITTED, I'D MAKE A FUNCTION TO SPIT OUT A FIELD BASED ON A FEW OPTIONS, OPTIONALLY SUPPLYING DATA -->

<div id='form_error'>&nbsp;</div>
    <input type='hidden' name='id' value='<?=$id?>' />

    <div class='form_label'>
        <label>First Name:</label>
    </div>
    <div class='form_field'>
        <input type='text' id='first_name' name='first_name' value='<?=$first_name?>' size=16 />
    </div>

    <div class='form_label'>
    <label>Last Name:</label>
    </div>
    <div class='form_field'>
    <input type='text' id='last_name' name='last_name' value='<?=$last_name?>' size=16 />
    </div>
    
    <div class='form_label'>
    <label>Email:</label>
    </div>
    <div class='form_field'>
    <input type='text' id='email' name='email' value='<?=$email?>' size=32 />
    </div>
    
    <div class='form_label'>
    <label>Mobile Number:</label>
    </div>
    <div class='form_field'>
    <input type='text' id='mobile_number' name='mobile_number' maxlength=10 value='<?=$mobile_number?>' size=32 />
    </div>
    
    <div class='form_label'>
    <label>Address:</label>
    </div>
    <div class='form_field'>
    <input type='text' id='address' name='address' value='<?=$address?>' size=32 />
    </div>
    
    <div class='form_label'>
    <label>City:</label>
    </div>
    <div class='form_field'>
    <input type='text' id='city' name='city' value='<?=$city?>' size=32 />
    </div>
    
    <div class='form_label'>
    <label>State:</label>
    </div>
    <div class='form_field' id='state_field_container'>
    <select id='state' name='state'>
    <?php
    foreach($localeData->countries->{$country} as $stateOption) {
        $selected = ($state === $stateOption->state_name ? ' selected': '');
        echo "<option value='" . $stateOption->state_name . "'" . $selected . ">" . $stateOption->state_name . "</option>";
    }
    ?>
    </select>
    </div>
    
    <div class='form_label'>
    <label>Zip:</label>
    </div>
    <div class='form_field'>
    <input type='text' id='zip' name='zip' maxlength=5 value='<?=$zip?>' size=32 />
    </div>
    
    <div class='form_label'>
    <label>Country:</label>
    </div>
    <div class='form_field'>
        <select id='country' name='country' onchange='Main.updateStates()'>
            <?php
            foreach($localeData->countries as $countryOption => $states) {
                $selected = ($country === $countryOption ? ' selected': '');
                echo "<option value='" . $countryOption . "'" . $selected . ">" . $countryOption . "</option>";
            }
            ?>
        </select>
    </div>
    
    <div class='form_label'>
    <label>Timezone:</label>
    </div>
    <div class='form_field'>
    <input type='text' id='timezone' name='timezone' value='<?=$timezone?>' size=32 />
    </div>

    <div class='form_submit'>
        <input type='button' value='Submit' onClick='Main.submitEditForm()' />
    </div>
</form>