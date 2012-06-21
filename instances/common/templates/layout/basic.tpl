<!DOCTYPE html>
<html lang="en">
{ $modules.head}
<body>

<div class="container">

{	$modules.header}

{	$modules.system_messages}

{block name="body"}{/block}

	<footer class="footer">
		<p>Powered by Sifo, 2009-{$smarty.now|date_format:"%Y"}</p>
	</footer>

</div> <!-- /container -->

</body>
</html>