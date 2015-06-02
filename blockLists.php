<?php
class_exists('Setup', false) or include('classes/Setup.class.php');
class_exists('Utilities', false) or include('classes/Utilities.class.php');
class_exists('_MySQL', false) or include('classes/_MySQL.class.php');

if(Utilities::isLoggedIn()===false){
	header('Location: login.php?location='.urlencode('hosts.php'));
	exit();
}
$blockListId = array_key_exists('blockListId', $_POST) ? (int)$_POST['blockListId'] : 0;
$toggle = array_key_exists('toggle', $_POST) ? (int)$_POST['toggle'] : 0;

$titlePreFix = "Hosts";

$user = Utilities::getAccount();
$mysql = new _MySQL();
$mysql->connect(Setup::$connectionArray);
if($blockListId !== 0){
	if($toggle==0){
		$mysql->runQuery("
			update blockLists
			set isActive = '0'
			where blockListId = $blockListId");
	}else{
		$mysql->runQuery("
			update blockLists
			set isActive = '1'
			where blockListId = $blockListId");
	}
	Utilities::setBlockLists(true);
	exit();
}

$sql = "
select *
from blockLists
order by isActive desc, importance desc, host, monitorType
";
$rs = $mysql->runQuery($sql);

include('header.inc.php');
include('accountSubnav.inc.php');
?>

<script src="js/jquery.tablesorter.min.js"></script>

<script>
$(document).ready(function() {
	$("#blockListTable").tablesorter();
	$(".blockListLinks").click( function(event) {
		var blockListId = $("#"+event.target.id).data("blocklistid");
		toggleBlacklist(blockListId);
		return false;
	});
});

function toggleBlacklist(blockListId){
	var status = $("#bl-"+blockListId).data("blstatus");
	if(status == 1) {
		status = 0;
	}else{
		status = 1;
	}
	$.post("blockLists.php", {blockListId: blockListId, toggle: status} )
		.done(function( data ) {
			if(status==1){
				$("#bl-"+blockListId).removeClass('glyphicon-remove');
				$("#bl-"+blockListId).addClass('glyphicon-ok');
			}else{
				$("#bl-"+blockListId).removeClass('glyphicon-ok');
				$("#bl-"+blockListId).addClass('glyphicon-remove');
			}
			$("#bl-"+blockListId).data("blstatus", status);
		});
}
</script>

<div class="panel panel-default">
	<div class="panel-body">
		<a class="glyphicon glyphicon-ok"></a> - Enabled<br>
		<a class="glyphicon glyphicon-remove"></a> - Disabled<br>
	</div>
</div>

<div class="table-responsive">
	<table id="blockListTable" class="tablesorter table table-bordered table-striped">
		<thead>
			<tr>
				<th>Status</th>
				<th>Blacklist</th>
				<th>Type</th>
				<th>Description</th>
				<th>Importance</th>
			</tr>
		</thead>
		<tbody>
		<?php
		while($row = mysqli_fetch_array($rs, MYSQL_ASSOC)){
			echo('<tr>');
			echo('<td style="text-align: center;">');
			if($row['isActive']==0){
				echo('<a data-blstatus="0" data-blocklistid="'.$row['blockListId'].'" id="bl-'.$row['blockListId'].'" class="blockListLinks glyphicon glyphicon-remove" href="#"></a></td>');
			}else{
				echo('<a data-blstatus="1" data-blocklistid="'.$row['blockListId'].'" id="bl-'.$row['blockListId'].'" class="blockListLinks glyphicon glyphicon-ok" href="#"></a></td>');
			}
			echo('<td style="white-space: nowrap"><a target="_blank" href="'.$row['website'].'">'.$row['host'].'</a></td>');
			echo('<td style="white-space: nowrap">'.($row['monitorType']=='ip' ? 'IP' : 'Domain').'</td>');
			echo('<td>'.$row['description'].'</td>');
			echo('<td style="text-align: center;">');
			switch($row['importance']){
				case 3: echo('<span class="label label-primary">High</span>'); break;
				case 2: echo('<span class="label label-info">Medium</span>'); break;
				case 1: echo('<span class="label label-default">Low</span>'); break;
			}
			echo('</td>');
			echo('</tr>');
		}
		$mysql->close();
		?>
		</tbody>
	</table>
</div>

<?php include('footer.inc.php'); ?>