<title>Cod detail </title>     
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
						<div class="invoice-box">
							<table cellpadding="0" cellspacing="0" >
								<tr class="top">
									<td colspan="14">
										<table>
											<tr>
												<td class="title">
													<img src="<?=base_url();?><?=site_configTable('logo');?>" style="  width:150px !important">

												</td>  

												<td> 
												<br>
												<br><br>
												<strong>Driver</strong>:<?php echo'<strong>'.$messangerName.'</stong>'?><br>
													
												</td>
											</tr>
										</table>
									</td>
								</tr> 
								<tr class="heading" border="1">
									   <th>Sn. No.</th>
										<th>AWB #</th>
										<th>Ref. #</th>
										<th>Product Type</th>
										<th>Shipment Type</th>
										<th>Shipment Desc.</th>
										<th>COD Amount</th>
      									<th>Status</th>
								</tr>


								
								
						<?php 
								$i=0;
							foreach($data1 as $menifest){	
							$i++;
							$totalAmount	+=	$menifest['total_cod_amt'];  
							echo '<tr class="item"> 
										<td align="center">'.$i.'</td> 
										<td>"'.$menifest['slip_no'].'"</td>
										<td align="center">'.$menifest['booking_id'].'</td>
										<td align="center">'.$menifest['nrd'].' </td>
										<td align="center">'.$menifest['mode'].'</td>
										<td align="center">'.$menifest['status_describtion'].'</td> 
										<td align="center">'.$menifest['total_cod_amt'].'</td> 
										<td align="center">'.status_main_cat($menifest['delivered']).'</td>       
									</tr>';
							} 
							?>
							</table>

						</div>
						 <br/>
						 <span style="float:left!important">Cod detail of <?php echo'<strong>'.$viewdate.'</stong>'?>  </span>
						 <br/>

						 <div style="width:100%">
							<div id="signaturetitle">  Finance team : </div>
							<div id="signature" style="border:1px solid #000; width:120px; line-height:15px;">  </div>
						</div>
						<div style="width:50%;float: right;margin-top:-60px">  
							<div id="signaturetitle">  Driver Signature: </div>
							<div id="signature" style="border:1px solid #000; width:120px; line-height:15px;">  </div>
						</div>
						<div class="footer"><b>Total Order</b> :  <?php echo'<strong>'.$totalCount.'</stong>'?> <br /> <b> Total Due:</b>  <?php echo'<strong>'.$totalAmount.'</stong>'?>  </div> 
					 
						<br />
</div>						
<script type="text/javascript">
window.print();
</script>