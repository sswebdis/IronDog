<h2>Подобрать Маршрут</h2>
<form class="form-inline">
	<input type="text" class="input-small" placeholder="Откуда">
	<input type="text" class="input-small" placeholder="Куда">
	<button type="submit" class="btn">Фасс!</button>
	<br>
	<?php
	foreach($trains as $train)
	{
		echo $train->id .'-' . $train->actual_to .'<br />';
	}
	?>
</form>
