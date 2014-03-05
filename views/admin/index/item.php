<?php

echo head(array('title' => "Import a single item"));
?>
<form method='post'>
<p>
<label for='api_url'>Api Url</label><input name='api_url' type='text' />
</p>
<p>
<label for='key'>Key</label><input name='key' type='text' />
</p>
<p>
<label for='item_id'>Item Id</label><input name='item_id' type='text' />
</p>

<button name='submit'>Submit</button>
</form>
<?php echo foot(); ?>