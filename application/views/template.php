<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="description" content="<?php echo $description; ?>" />
	<?php foreach($styles as $style): ?>
		<link href="<?php echo URL::base(); ?>public/css/<?php echo $style; ?>.css"
			  rel="stylesheet" type="text/css" />
	<?php endforeach; ?>
	<title><?php echo $title; ?></title>
</head>

<body>
<div class="layer">
	<div class="container">
		<div class="header"><h1>IronDog</h1></div>

		<div class="sidebar">
			<h3>Меню</h3>
			<br />
			<ul>
				<li><a href="<?php echo URL::site(); ?>">Главная</a></li>
				<li><a href="<?php echo URL::site('page/about'); ?>">О сайте</a></li>
<!--				<li><a href="--><?php //echo URL::site('page/contacts'); ?><!--">Kонтакты</a></li>-->
			</ul>
		</div>
		<div class="content"><?php echo $content; ?></div>

		<div class="clearing"></div>
		<div class="footer">2013 DOU-Hackaton-Reload</div>
	</div>
</div>
</body>
</html>