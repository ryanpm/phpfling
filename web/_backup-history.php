<?php 

$opendir = opendir($config['data_path'].'backup/');

?>
<div style="height:300px;display:block;overflow-y:scroll">

<table width="100%" class="table-list" cellspacing="0" cellspacing="0">
	<thead>
		<tr>
			<th style="text-align:left">Date / Time</th>
			<th width="150" style="text-align:center">Total Files</th>
		</tr>
	</thead>
	<tbody>
	<?php while( $dir = readdir($opendir) ): if( $dir == '.' or $dir == '..' ) continue; 

	$statusfile = $config['data_path'] .'backup/'.$dir.'/_status';

	$status = json_decode(file_get_contents($statusfile), true);

	?>
		<tr>
			<td ><?php echo date('Y m d h:s:ia', $dir) ?></td>
			<td style="text-align:center"><?php echo count($status['modified']) ?></td>
		</tr>
	</tbody>
	<?php endwhile ?>

</table>

</div>