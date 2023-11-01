<div class="top-mnu">
	<a href="/" <?=$_SERVER['REQUEST_URI']=='/'?'class="top-mnu-selected"':''?>>Сводка</a>
	<a href="/prices.php" <?=$_SERVER['REQUEST_URI']=='/prices.php'?'class="top-mnu-selected"':''?>>Парсинг цен</a>
	<a href="/parse.php" <?=$_SERVER['REQUEST_URI']=='/parse.php'?'class="top-mnu-selected"':''?>>Парсинг АЗС</a>
	<a href="/azs.php" <?=substr($_SERVER['REQUEST_URI'],0,8)=='/azs.php'||substr($_SERVER['REQUEST_URI'],0,16)=='/azs_foreing.php'?'class="top-mnu-selected"':''?>>АЗС</a>
	<?php //if ($_SESSION['admin']):?>
	<a href="/fuels.php" <?=substr($_SERVER['REQUEST_URI'],0,10)=='/fuels.php'?'class="top-mnu-selected"':''?>>Топливо</a>
	<?php //endif;?>
	<a href="/help.pdf">Помощь</a>
	<a href="/logout.php">Выход</a>
</div>
<?php if (substr($_SERVER['REQUEST_URI'],0,8)=='/azs.php'||substr($_SERVER['REQUEST_URI'],0,16)=='/azs_foreing.php'):?>
<div class="top-mnu-sub">
	<a href="/azs.php" <?=substr($_SERVER['REQUEST_URI'],0,8)=='/azs.php'?'class="top-mnu-sub-selected"':''?>>Собственные АЗС</a>
	<a href="/azs_foreing.php" title="АЗС не являющиеся конкурентами ни одной из собственных АЗС" <?=substr($_SERVER['REQUEST_URI'],0,16)=='/azs_foreing.php'?'class="top-mnu-sub-selected"':''?>>Сторонние АЗС</a>
</div>
<?php endif;?>