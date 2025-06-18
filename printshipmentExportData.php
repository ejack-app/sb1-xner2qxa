
<div style="display: none;">

    <table id="printTable" class="table table-striped table-bordered table-hover">  

        <thead>
            <tr>
                <th>Date</th>
                <th>Picked up Date</th>
                <th>Reference#</th>
                <th>Shipper Reference#</th>
                <th>AWB</th>
                <th>Delivery Attempts</th>
                <th>Call Attempts</th>
                <th>Origin</th>
                <th>HUB</th>
                <th>Destination</th>
                <th>Sender</th>
                <th>Sender Address</th>
                <th>Sender Zip</th>
                <th>Sender Phone</th>
                <th>Reciever</th>
                <th>Forward Through</th>
                <th>Airway Bill No.</th>
                <th>Reciever Address</th>
                <th>Reciever Zip</th>
                <th>Reciever Phone</th>
                <th>Mode</th>
                <th>Status</th>
                <th>COD Amount</th>
                <th>Pay Status</th>
                <th>Sub Category 1</th>
                <th>driver comment 1</th>
                <th>Sub Category 2</th>
                <th>Driver Comment 2</th>
                <th>Sub Category 3</th>
                <th>Driver Comment 3</th>
                <th>Shelv location</th>
                <th>Schedule</th>
                <th>Schedule Channel</th>
                <th>On Hold Date</th>
                <th>On Hold</th>
                <th>On Hold Reason</th>
                <th>On Hold Confirm</th>
                <th>Driver Name</th>
                <th>Driver Code</th>
                <th>Driver Mobile</th>
                <th>Driver Supplier</th>
                <th>Warehouse</th>
                <th>Shelve</th>
                <th>UID Account</th>
                <th>Schedule Date</th>
                <th>Time Slot</th>
                <th>Area Street</th>
                <th>Area</th>
                <th>latitude , longitude</th>
                <th>Update Date</th>
                <th>Deliver Date</th>
                <th>Pieces</th>
                <th>Weight</th>
                <th>Description</th>
                <th>Forward Through</th>
                <th>Forward Awb</th>
                <th>Forward Date</th> 
            </tr>
        </thead> 

        <tbody>
            <?php foreach($results as $result):?>
            <tr>
                <td><?php echo $result['entrydate']?></td>
                <td><?php echo $result['pickup_date']?></td>
                <td><?php echo $result['booking_id']?></td>
                <td><?php echo $result['shippers_ref_no']?></td>
                <td><?php echo $result['slip_no']?></td>
                <td><?php echo $result['d_attempt']?></td>
                <td><?php echo $result['call_attempt']?></td>
                <td><?php echo $result['origin']?></td>
                <td><?php echo $result['destination']?></td>
                <td><?php echo $result['sender_name']?></td>
                <td><?php echo $result['sender_address']?></td>
                <td><?php echo $result['sender_zip']?></td>
                <td><?php echo $result['sender_phone']?></td>
                <td><?php echo $result['reciever_name']?></td>
                <td><?php echo $result['']?></td>
                <td><?php echo $result['']?></td>
                <td><?php echo $result['reciever_zip']?></td>
                <td><?php echo $result['reciever_phone']?></td>
                <td><?php echo $result['mode']?></td>
                <td><?php echo $result['']?></td>
                <td><?php echo $result['total_cod_amt']?></td>
                <td><?php echo $result['pay_Invoice_status']?></td>
                <td><?php echo $result['sub_category']?></td>
                <td><?php echo $result['']?></td>
                <td><?php echo $result['']?></td>
                <td><?php echo $result['']?></td>
                <td><?php echo $result['']?></td>
                <td><?php echo $result['']?></td>
                <td><?php echo $result['']?></td>
                <td><?php echo $result['']?></td>
                <td><?php echo $result['']?></td>
                <td><?php echo $result['onHold_Date']?></td>
                <td><?php echo $result['onHold_Days']?></td>
                <td><?php echo $result['onHold_Reason']?></td>
                <td><?php echo $result['onHold_Confirm']?></td>
                <td><?php echo $result['']?></td>
                <td><?php echo $result['']?></td>
                <td><?php echo $result['']?></td>
                <td><?php echo $result['']?></td>
                <td><?php echo $result['']?></td>
                <td><?php echo $result['shelv_no']?></td>
                <td><?php echo $result['uniqueid']?></td>
                <td><?php echo $result['schedule_date']?></td>
                <td><?php echo $result['time_slot']?></td>
                <td><?php echo $result['area_street']?></td>
                <td><?php echo $result['area']?></td>
                <td><?php echo $result['']?></td>
                <td><?php echo $result['update_date']?></td>
                <td><?php echo $result['delever_date']?></td>
                <td><?php echo $result['pieces']?></td>
                <td><?php echo $result['weight']?></td>
                <td><?php echo $result['status_describtion']?></td>
                <td><?php echo $result['frwd_throw']?></td>
                <td><?php echo $result['frwd_awb_no']?></td>
                <td><?php echo $result['']?></td>
                
            </tr>
            
            <?php endforeach;?>
            
        </tbody>  

    </table>
</div>
<script src="<?php echo base_url()."assets/"; ?>vendors4/jquery/dist/jquery.min.js"></script>
<script type="text/javascript" src="<?php echo base_url()."assets/"; ?>exports/jszip.js"></script>
<script type="text/javascript" src="<?php echo base_url()."assets/"; ?>exports/jszip-utils.js"></script>
<script type="text/javascript" src="<?php echo base_url()."assets/"; ?>exports/FileSaver.js"></script>
<SCRIPT>

    function create_zip() {
        var tab_text = '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
        tab_text = tab_text + '<head><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet>';
        tab_text = tab_text + '<x:Name>Test Sheet</x:Name>';
        tab_text = tab_text + '<x:WorksheetOptions><x:Panes></x:Panes></x:WorksheetOptions></x:ExcelWorksheet>';
        tab_text = tab_text + '</x:ExcelWorksheets></x:ExcelWorkbook></xml></head><body>';
        tab_text = tab_text + "<table border='1px'>";
//get table HTML code
        tab_text = tab_text + $('#printTable').html();
        tab_text = tab_text + '</table></body></html>';

        /*var zip = new JSZip();
         zip.add(Date()+"_manifest details.xls", tab_text);
         content = zip.generate();
         zip.file(Date()+"_manifest details.xls"); // the file
         location.href="data:application/zip;base64," + content;*/

        var zip = new JSZip();
        zip.file(Date() + " All Shipments.xls", tab_text);
        zip.generateAsync({type: "blob"})
                .then(function (content) {
                    saveAs(content, Date() + " All Shipmentst.zip");
                });
                
                
                window.location.href="/all_shipment";


    }
    create_zip();
</SCRIPT>

