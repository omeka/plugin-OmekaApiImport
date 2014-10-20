<?php
    echo head(array('title' => __("Import an Omeka Site")));
?>

<?php echo flash(); ?>
<?php if(isset($import)): ?>
<h2><?php echo __("Most recent import"); ?></h2>
<p><?php echo __("Started %s", $import->date); ?></p>
<p><?php echo __("API Url"); ?>: <?php echo $import->endpoint_uri; ?></p>
<p><?php echo __("Status"); ?>: <?php echo __('%s', $import->status); ?>

<a href=""><?php echo __("Click here to update status.");?></a></p>
<?php endif;?>

<form method='post'>
<section class="seven columns alpha">
<h2><?php echo __("Start new import"); ?></h2>

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
        <label for='omeka_api_import_override_element_set_data'><?php echo __('Override Element Set data?'); ?></label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php echo __("Checking this box will overwrite your local information about Element comments, Item Type descriptions, and Element Set descriptions."); ?></p>
        <div class="input-block">
            <?php echo $this->formCheckbox('omeka_api_import_override_element_set_data',
                 get_option('omeka_api_import_override_element_set_data'),
                 array(), 
                 array(1,0));  ?>
        </div>
    </div>
</div>

<?php if(!empty($urls)): ?>
<h2 style="clear:both"><?php echo __("Undo Imports"); ?></h2>

<div class="field">
    <div class="two columns alpha">
        <label><?php echo __('Undo previous imports'); ?></label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"></p>
        <div class="input-block">
        <?php foreach($urls as $index=>$url): ?>
            <div>
                <input name='undo[]' value='<?php echo $index; ?>' type='checkbox' /> <span class='url'><?php echo $url; ?></span>
            </div>
        <?php endforeach; ?>
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
