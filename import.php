<html>
<head>
 <title>How to Import Excel Data into Mysql in Codeigniter</title>


</head>

<body>
 <div class="container">
  <br />
  <h3 align="center">How to Import Excel Data into Mysql in Codeigniter</h3>
  <form method="post" id="import_form" enctype="multipart/form-data" action="<?= base_url() ?>/ShipmentManagement/import">
   <p><label>Select Excel File</label>
   <input type="file" name="file" id="file"  accept=".xls, .xlsx" /></p>
   <br />
   <input type="submit" name="import" value="Import" class="btn btn-info" />
  </form>
  <br />
  <div class="table-responsive" id="customer_data">

  </div>
 </div>
</body>
</html>