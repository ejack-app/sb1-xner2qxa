<table width="100%" border="0" cellspacing="0" cellpadding="0" align="center">
   <tr>
      <td>
         <table width="100%" border="0" cellspacing="2" cellpadding="5" align="center" bgcolor="#FFFFFF" style="border:1px solid; margin-bottom:10px;">
            <tr>
               <td>
                  <img src="<?= base_url(); ?><?= site_configTable('logo'); ?>" style="width:100px; height:50px;" class="invert" />
               </td>
               <td>
                  <strong>Support : </strong> <?= site_configTable('email'); ?>
                  <br />
                  <strong>Call :</strong> <?= site_configTable('phone'); ?><br />

               </td>
               <td>
                  <strong> <?= site_configTable('company_name'); ?> </strong>
                  <br />
                  <?= site_configTable('company_address'); ?>
                  <br />


               </td>

            </tr>
            <tr bgcolor="#CCCCCC">
               <td class="t_alin">Delivery Date</td>
               <td class="t_alin">Delivery Sheet ID</td>
               <td class="t_alin">Route Code</td>
               <td class="t_alin">Courier Name</td>
               <td class="t_alin">Vehicle Reg No</td>
               <td class="t_alin"></td>
               <td class="t_alin"></td>
               <td class="t_alin"></td>

            </tr>
            <tr>
               <td class="a_bg"><?= $data1[0]['drs_date']; ?></td>
               <td class="a_bg"><?php

                                 $base64 = 'data:image/' . $type . ';base64,' . barcodeRuntime($data1[0]['drs_unique_id']);
                                 echo '<img src="' . $base64 . '">';
                                 ?><br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?= $data1[0]['drs_unique_id']; ?></td>
               <td class="a_bg"><?= $data1[0]['messenger_code']; ?></td>
               <td class="a_bg"><?= $data1[0]['messenger_name']; ?></td>
               <td class="a_bg"><?= $data1[0]['vehicle_number']; ?></td>
            </tr>
            <tr>
               <td></td>
            </tr>
            <tr bgcolor="#CCCCCC">
               <td class="t_alin">Total Shipments</td>
               <td class="t_alin">Total Pack</td>
               <td class="t_alin">Start Km</td>
               <td class="t_alin">Amount Due</td>
               <td class="t_alin">Amt Collected</td>
               <td class="t_alin">End Km</td>
               <td class="t_alin">Handed To</td>
               <td class="t_alin">Sign</td>
            </tr>
            <tr>
               <td class="a_bg"><?= $total; ?></td>
               <td class="a_bg"><?= $totalpack; ?></td>
               <td class="a_bg">&nbsp;</td>
               <td class="a_bg"><?= $ammountdue; ?></td>
               <td class="a_bg">&nbsp;</td>
               <td class="a_bg">&nbsp;</td>
               <td class="a_bg">&nbsp;</td>
               <td class="a_bg">&nbsp;</td>
               <td class="a_bg">&nbsp;</td>
            </tr>

            <tr>
               <td colspan="8"></td>
            </tr>
         </table>
      </td>
   </tr>


   <tr>
      <td>
         <table width="100%" border="0" cellspacing="2" cellpadding="2" align="center" style="border:1px solid #000;">
            <tr bgcolor="#CCCCCC">
               <td class="t_alin">Sr. No</td>
               <td class="t_alin">AWB No</td>
               <td class="t_alin">Bookig ID</td>

               <td class="t_alin">Shipper Name</td>
               <td class="t_alin">Receiver Name/Address</td>
               <td class="t_alin">No of Pack.</td>
               <td class="t_alin">Amount</td>
               <td class="t_alin">Phone No</td>
               <td class="t_alin">Time of &nbsp; Delievery</td>
               <td class="t_alin">Receieved By</td>
               <td class="t_alin">Remarks</td>
               <td class="t_alin">Signature</td>
            </tr>

            <?php
            foreach ($data1 as $key => $pdata) {
               $counter = $key + 1;
               $base64 = 'data:image/' . $type . ';base64,' . barcodeRuntime($pdata['slip_no']);
               //   echo '<td>Bookingid</td>';
               echo '<tr style="margin-bottom:5px; ">
   <td class="a_bg">' . $counter . '</td>
   <td class="a_bg"><img src="' . $base64 . '">
   <br />' . $pdata['slip_no'] . '</td>
   <td>'.$pdata['booking_id'].'</td>
      <td class="a_bg">' . $pdata['sender_name'] . '';

               if ($pdata['time_slot'] != "")
                  echo '<span></br></span>
<strong>' . $pdata['time_slot'] . '</stong>';
               else
                  echo '--';
               if ($pdata['schedule_type'] != "")
                  echo ':<strong>' . $pdata['schedule_type'] . '</stong>';
               else
                  echo '--';
               echo '</td>

   <td class="a_bg">' . $pdata['reciever_name'] . ',<br />
' . $pdata['reciever_address'] . '</td>
<td class="a_bg">' . $pdata['pieces'] . '</td>
  <td class="a_bg">';
               if ($pdata['client_type'] == 'B2C') {
                  if ($pdata['mode'] == 'COD') {
                     $x = $pdata['total_cod_amt'];
                     $y = $pdata['cod_fees'];
                     $z = $pdata['service_charge'];
                     $codAmount = $x + $y + $z;
                     echo 'COD(' . $codAmount . ')';
                  }
               } else
                  echo '---';
               echo '</td>

   <td class="a_bg">
   ' . $pdata['reciever_phone'] . '&nbsp;
   ' . $pdata['reciever_fax'] . '</td>

   <td class="a_bg" align="center"><input type="text" style="width:92%; height:100%;" /></td>
   <td class="a_bg" align="center"><input type="text" style="width:92%; height:100%;" /></td>
   <td class="a_bg" align="center"><input type="text" style="width:92%; height:100%;" /></td>
   <td class="a_bg" align="center"><input type="text" style="width:92%; height:100%;" /></td>
   </tr>';
            }
            ?>

            <tr>
               <td colspan="8"></td>
            </tr>
         </table>
      </td>
   </tr>

   </td>
   </tr>

</table>

<script type="text/javascript">
   window.print();
</script>