<?php

//if ($modx->getOption('manager_language') == 'ru') {

echo 'dfdsfsdfsdfdsfds';

?>

<style>
    #setup_form_wrapper {font: normal 12px Arial;line-height:18px;}
    #setup_form_wrapper ul {margin-left: 5px; font-size: 10px; list-style: disc inside;}
    #setup_form_wrapper a {color: #08C;}
    #setup_form_wrapper small {font-size: 10px; color:#555; font-style:italic;}
    #setup_form_wrapper label {color: black; font-weight: bold;}
</style>

<div id="setup_form_wrapper">
    // own lexicon? resolvers works after installation, so it should ne already available

    <div>Тут можно выбрать, в каком месте переключить язык. В системных настройках или на уровне пользователя (пока что).</div>

    <fieldset>
        <label for="blt-target">System</label>
        <input type="radio" checked="checked" id="btl-target" name="target">

        <label for="blt-target-2">User</label>
        <input type="radio" checked="checked" id="btl-target-2" name="target">

    </fieldset>

</div>
