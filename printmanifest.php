<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>


<style>
.container_set{
	width:95%;
	margin:0 auto;
}
.p_tab{
	font-size:22px;
	font-weight:bold;
}
.p_tab img{
	max-height:50px;
	margin-bottom:20px;
}
</style>


<div style=" text-align:center;">
	<p class="p_tab"> <img src="<?=base_url();?><?=site_configTable('logo');?>" alt="" /><br />
		<span  style="font-size:22px; font-weight:bold;"><?=site_configTable('company_name');?></span><br />
		<!--<span style="font-size:14px; font-weight:500;">{$company_address}</span>--> &nbsp;<?php if($data1[0]['return_menifest']=='Y') echo 'Return Shipment manifest';    
         $data1[0]['shipper']; ?></p>
</div>
<div class="container_set">
	<div class="row">
		<div class="col-md-2 col-xs-4">
			<div class="form-group">
				<strong>Manifest No
                :</strong>  <?php
  echo $data1[0]['uniqueid'];
	$base64 = 'data:image/' . $type . ';base64,' . barcodeRuntime($uniqueid); 
	echo '<img src="'.$base64.'">';
	 ?>
       
				<p style=" font-weight:800; text-align:center;"><?=$uniqueid;?></p> 	
			</div>	
		</div>
		<div class="col-md-2 col-xs-4">
			<div class="form-group">
				<strong>Date</strong> : <?=$data1[0]['mdate'];?>
			</div>
		</div>
		<div class="col-md-4 col-xs-4">
			<div class="form-group">
				<strong>From</strong> : &nbsp; <?=getcitybyid($data1[0]['mfrom']);?>
			</div>	
		</div>
		<div class="col-md-4 col-xs-4">
			<div class="form-group">
				<strong>To</strong> : &nbsp; <?=getcitybyid($data1[0]['mto']);?>
			</div>	
		</div>
		</div>
	
	
	<table width="100%" border="0" cellspacing="2" cellpadding="5" align="center"  class="table table-condensed table-bordered">
		<tr style="background-color:#F1F1F1">
			<th width="5%">S.No</th>
			<th width="10%">AWB No</th>
			<th width="15%">Shipper</th>
			<th width="15%">Reciever</th>
			<!-- <th width="5%">sent</th> -->
			<th width="5%">pcs</th>
			<th width="12%">Origin</th>
			<th width="12%">Destination</th>
			<th width="15%">Barcode</th>
		</tr>
	<?php 
	$totalpic=0;
	$totalscaned=0;
	//print_r($data1);
	foreach($data1 as $key=>$value)
	{
		$counter=$key+1;
		if ($counter % 2 == 0)
		$class1='style="background-color:#F4F4F4"';
		else
		$class1='';
		$base64 = 'data:image/' . $type . ';base64,' . barcodeRuntime($value['slip_no']); 
		$totalpic+=$value['pcs'];
		$totalscaned+=$value['scaned'];
		// <td style="padding-left:10px">'.$value['scaned'].'</td>  
		echo'<tr '.$class1.'>
			<td style="padding-left:10px">'.$counter.'</td>
			<td style="padding-left:10px">'.$value['slip_no'].'</td>
			<td style="padding-left:10px">'.$value['sender_name'].'</td>
			<td style="padding-left:10px">'.$value['reciever_name'].'</td>
			<td style="padding-left:10px">'.$value['pcs'].'</td>
			
			<td style="padding-left:10px">'.$value['sender_city'].'</td>
			<td style="padding-left:10px">'.$value['reciever_city'].'</td>
			<td>
         
            <img src="'.$base64.'" /><br />
				<span style=" margin-left:20px; font-size:14px; font-weight:800;">'.$value['awbillno'].'</span></td>
		</tr>';
	}
		?>
			
	</table>
	<div class="row">
		<div class="col-md-6 col-xs-6">
			<h4>Total Order :<?=$counter;?></h4> 
			<h4>Reciver name :</h4>
		</div>
		
		<div class="col-md-6 col-xs-6 text-center">
			<h4>Driver name :</h4>
			<h4>singature :</h4>
		</div>
	</div>
</div>

<script type="text/javascript">
window.print();
</script>