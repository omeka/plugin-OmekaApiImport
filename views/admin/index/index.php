<?php

echo head(array('title' => "Import an Omeka Site"));
?>
<form method='post'>
<p>
<label for='api_url'>Api Url</label><input name='api_url' type='text' />
</p>
<p>
<label for='key'>Key</label><input name='key' type='text' size="30" />
</p>

<p>
<label for='api_import_override_element_set_data'><?php echo __('Override Element Set data?'); ?></label>
<?php echo $this->formCheckbox('api_import_override_element_set_data',
                 get_option('api_import_override_element_set_data'),
                 array(), 
                 array(1,0));  ?>
                 
</p>
<button name='submit'>Submit</button>
</form>
<?php echo foot(); ?>