<?php




echo head(array('title' => "Import an Omeka Site"));
?>
<form method='post'>
<section class="seven columns alpha">


<div class="field">
    <div class="two columns alpha">
        <label for='api_url'><?php echo __("Remote API Url"); ?></label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"></p>
        <div class="input-block">
            <input name='api_url' type='text' />
        </div>
    </div>
</div>


<div class="field">
    <div class="two columns alpha">
        <label for='key'><?php echo __("API Key"); ?></label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"></p>
        <div class="input-block">
            <input name='key' type='text' size="30" />
        </div>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label for='api_import_override_element_set_data'><?php echo __('Override Element Set data?'); ?></label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"></p>
        <div class="input-block">
            <?php echo $this->formCheckbox('api_import_override_element_set_data',
                 get_option('api_import_override_element_set_data'),
                 array(), 
                 array(1,0));  ?>
        </div>
    </div>
</div>

<?php if(!empty($urls)): ?>
<h2><?php echo __("Undo Imports"); ?></h2>

<div class="field">
    <div class="two columns alpha">
        <label></label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"></p>
        <div class="input-block">
        <?php echo $this->formMultiCheckbox('undo', null, null, $urls); ?>
        </div>
    </div>
</div>
<?php endif; ?>


</section>

<section class="three columns omega">
    <div class='panel' id='save'>
        <input type='submit' class="submit big green button" name='submit' value="<?php echo __('Submit'); ?>" />
    </div>
</section>
</form>

<?php echo foot(); ?>
