<style>
						.invoice-box {
							max-width: 800px;
							margin: auto;
							padding: 10px;
							border: 1px solid #eee;
							box-shadow: 0 0 10px rgba(0, 0, 0, .15);
							font-size: 8px;
							line-height: 24px;
							font-family: "Helvetica Neue", "Helvetica", Helvetica, Arial, sans-serif;
							color: #555;
							height:850px
						}
						
						.invoice-box table {
							width: 100%;
							line-height: inherit;
							text-align: left;
						}
						
						.invoice-box table td {
							padding: 5px;
							vertical-align: top;
						}
						
						.invoice-box table tr td:nth-child(2) {
							text-align: right;
						}
						
						.invoice-box table tr.top table td {
							padding-bottom: 20px;
						}
						
						.invoice-box table tr.top table td.title {
							font-size: 18px;
							line-height: 45px;
							color: #333;
						}
						
						.invoice-box table tr.information table td {
							padding-bottom: 40px;
						}
						
						.invoice-box table tr.heading td {
							background: #eee;
							border-bottom: 1px solid #ddd;
							font-weight: bold;
						}
						
						.invoice-box table tr.details td {
							padding-bottom: 20px;
						}
						
						.invoice-box table tr.item td{
							border-bottom: 1px solid #eee;
						}
						
						.invoice-box table tr.item.last td {
							border-bottom: none;
						}
						
						.invoice-box table tr.total td:nth-child(2) {
							border-top: 2px solid #eee;
							font-weight: bold;
						}
						
						@media only screen and (max-width: 600px) {
							.invoice-box table tr.top table td {
								width: 100%;
								display: block;
								text-align: center;
							}
							
							.invoice-box table tr.information table td {
								width: 100%;
								display: block;
								text-align: center;
							}
						}
						table {
							font-family: arial, sans-serif;
							
						}

						td, th {
							
							
						} 
						/** RTL **/
						.rtl {
							direction: rtl;
							font-family: Tahoma, "Helvetica Neue", "Helvetica", Helvetica, Arial, sans-serif;
						}
						
						.rtl table {
							text-align: right;
						}
						
						.rtl table tr td:nth-child(2) {
							text-align: left;
						}
						.margin_top{
							margin-top:100%;
						}
						.footer {
						   position: fixed;
						   left: 250px;
						   bottom: 0;
						   width: 100%;
						   
						   color: #000;
						   text-align: center;
						}
						#signaturetitle { 
						  font-weight: bold;
						  font-size: 100%;
						}

						#signature {
						  text-align: center;
						  height: 30px;
						  word-spacing: 1px;
						}
						</style>
					</head> 
					<body>
						<div class="invoice-box" style="border: 1px solid #eee;box-shadow:none">
							<table >
								<tr class="top">
									<td colspan="14">
										<table>
											<tr>
												<td class="title">
													 <img src="<?=base_url();?><?=site_configTable('logo');?>" style="width:100px; height:50px;" class="invert" />

												</td>
												<td> 
												<br>
												<br><br>
												<?php if((!empty($start_date)) && (!empty($end_date))){?>
													<strong>Date</strong>:<?php echo'<strong>'.$start_date.'</stong>'?> -<?php echo'<strong>'.$end_date.'</stong>'?><br>
												<?php }else{ 
													$date=date('Y-m-d');
												
												?>
												<strong>Date</strong>:<?php echo'<strong>'.$date.'</stong>'?><br>
												<?php }?>	
												</td>
											</tr>
										</table>
									</td>
								</tr> 
							  
								<tr class="heading" border="1">
									 <th border="1">#</th>
									<th border="1">Unique id</th>.
									<th border="1">       
									
									Date
									</th>
									
									<th border="1">Driver </th>
									<th border="1">POD &nbsp;&nbsp;</th>
									<th border="1">NP&nbsp;&nbsp; </th>
									
									<th border="1">RTW &nbsp;&nbsp;</th>
									<th border="1">Total Ship &nbsp;</th>
									<th border="1">COD Amt </th> 
								</tr>
								
								
<?php 
		foreach($data1 as $key=>$pdata)
		{
			$counter=$key+1;
			$base64 = 'data:image/' . $type . ';base64,' . barcodeRuntime($pdata['drs_unique_id']); 
		   echo '<tr style="margin-bottom:5px; ">
		   <td class="a_bg">'.$counter.'</td>
		   <td class="a_bg"><img src="'.$base64.'">
		   <p>'.$pdata['drs_unique_id'].'</p></td>
		  		  
		  <td class="a_bg">';
			   echo'<span></br></span>
		<strong>'.$pdata['drs_date'].'</stong>';
		echo'</td>
		  <td class="a_bg">';
		 if ($pdata['messangerName']!="")
		echo'<strong>'.$pdata['messangerName'].'</stong>';
		
		echo'</td> 
		 
	<td class="a_bg">';
		 if ($pdata['POD']!="")
		echo'<strong>'.$pdata['POD'].'</stong>';
		
		echo'</td>
		<td class="a_bg">';
		 if ($pdata['NP']!="")
		echo'<strong>'.$pdata['NP'].'</stong>';
		
		echo'</td>
		
		<td class="a_bg">';
		 if ($pdata['totalall']!="")
		echo'<strong>'.$pdata['totalall'].'</stong>';
		
		echo'</td>
		<td class="a_bg">';
		 if ($pdata['total_ship']!="")
		echo'<strong>'.$pdata['total_ship'].'</stong>';
		
		echo'</td>
		
		<td class="a_bg">';
		
		echo'<strong>'.$pdata['COD_AMOUNT'].'</stong>';     
		
		echo'</td>
		   </tr>';
		}
   ?>
</table>
<br />  

<span style="float:right!important">Cod detail of <?php echo'<strong>'.$start_date.'</stong>'?> -<?php echo'<strong>'.$end_date.'</stong>'?></span>

<div style="width:100%">
							<div id="signaturetitle">  Finance team : </div>
							<div id="signature" style="border:1px solid #000; width:120px; line-height:15px;">  </div>
						</div>
						<div style="width:50%;float: right;margin-top:-50px">
							<div id="signaturetitle">  Cashier Signature: </div>
							<div id="signature" style="border:1px solid #000; width:120px; line-height:15px;">  </div>
						</div>
						<div class="footer"><b>Total Delivered Order</b> : <?php echo'<strong>'.$totalPOD.'</stong>'?><br /> <b> Total Due:</b> <?php echo'<strong>'.$totalAmount.'</stong>'?>  </div>
</div>						
<script type="text/javascript">
window.print();
</script>