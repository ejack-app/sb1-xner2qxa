<!DOCTYPE html>

<html lang="en" ng-app="myApp" class="">



<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <title>{{'lang_Wyyak'|translate}}</title>
    <style>
        .overlay {
            background-color: #FFFFFF;
            position: fixed;
            width: 100%;
            height: 100%;
            z-index: 9999999;
            top: 0px;
            left: 0px;
            opacity: .5;
            /* in FireFox */
            filter: alpha(opacity=50);
            /* in IE */
        }

        .loader {
            position: fixed;
            top: 34%;
            border-radius: 50%;
            border-top: 16px solid #BA223F;
            border-bottom: 16px solid #BA223F;
            width: 200px;
            height: 200px;
            -webkit-animation: spin 2s linear infinite;
            /* Safari */
            animation: spin 2s linear infinite;
            text-align: center;
            margin-left: 21%;
            z-index: 9999999999999 !important;
            opacity: .5;
            /* in FireFox */
            filter: alpha(opacity=50);
            /* in IE */
        }

        .loader88 {
            position: fixed;
            top: 5%;
            /* border: 16px solid #f3f3f3;*/
            border-radius: 50%;
            border-top: 16px solid #BA223F;
            border-bottom: 16px solid #BA223F;
            width: 200px;
            height: 200px;
            margin-left: 45%;
            z-index: 9999999999999 !important;
            -webkit-animation: spin 2s linear infinite;
            animation: spin 2s linear infinite;
        }

        /* Safari */
        @-webkit-keyframes spin {
            0% {
                -webkit-transform: rotate(0deg);
            }

            100% {
                -webkit-transform: rotate(360deg);
            }
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .noscroll {
            overflow: hidden;
        }

        .dialog {
            z-index: 999999;
            position: fixed;
            left: 0;
            top: 0;
            width: 100%;
            min-height: calc(100%);
            height: auto !important;
            background: #BA223F;
        }

        .dialog[ng-cloak] {
            display: none;
        }

        .dialog .content {
            position: absolute;
            width: 100%;
            top: 50%;
            text-align: center;
            transform: translateY(-50%);
        }

        .fa-cog {
            font-size: 3em;
        }
    </style>
    <base href="https://lm.wyyak.com/">
    <!-- Favicon -->
    <!-- Favicon -->
    <link rel="shortcut icon" href="favicon.ico">
    <link rel="icon" href="favicon.ico" type="image/x-icon">

    <!-- vector map CSS -->

    <link href=" https://fonts.googleapis.com/css?family=Roboto:400,300,100,500,700,900" rel="stylesheet" type="text/css">
    <link href="https://tamco.fast-option.com/templates/customer/assets/css/icons/icomoon/styles.css" rel="stylesheet" type="text/css">
    <link href="<?php echo base_url() . "assets/"; ?>vendors4/vectormap/jquery-jvectormap-2.0.3.css" rel="stylesheet" type="text/css" />

    <!-- Toggles CSS -->
    <link href="<?php echo base_url() . "assets/"; ?>vendors4/jquery-toggles/css/toggles.css" rel="stylesheet" type="text/css">
    <link href="<?php echo base_url() . "assets/"; ?>/vendors4/jquery-toggles/css/themes/toggles-light.css" rel="stylesheet" type="text/css">


    <!-- ION CSS -->
    <link href="<?php echo base_url() . "assets/"; ?>vendors4/ion-rangeslider/css/ion.rangeSlider.css" rel="stylesheet" type="text/css">
    <link href="<?php echo base_url() . "assets/"; ?>vendors4/ion-rangeslider/css/ion.rangeSlider.skinHTML5.css" rel="stylesheet" type="text/css">

    <!-- select2 CSS -->
    <link href="<?php echo base_url() . "assets/"; ?>vendors4/select2/dist/css/select2.min.css" rel="stylesheet" type="text/css" />

    <!-- Pickr CSS -->
    <link href="<?php echo base_url() . "assets/"; ?>vendors4/pickr-widget/dist/pickr.min.css" rel="stylesheet" type="text/css" />

    <!-- Daterangepicker CSS -->
    <link href="<?php echo base_url() . "assets/"; ?>vendors4/daterangepicker/daterangepicker.css" rel="stylesheet" type="text/css" />
    <!-- Toastr CSS -->
    <link href="<?php echo base_url() . "assets/"; ?>vendors4/jquery-toast-plugin/dist/jquery.toast.min.css" rel="stylesheet" type="text/css">

    <!-- Custom CSS -->
    <link href="<?php echo base_url() . "assets/"; ?>dist/css/style.css" rel="stylesheet" type="text/css">


    <link href="<?php echo base_url() . "assets/"; ?>styleloder.css" rel="stylesheet" type="text/css">



    <!-- jQuery -->
    <script src="<?php echo base_url() . "assets/"; ?>vendors4/jquery/dist/jquery.min.js"></script>
    <script type="text/javascript" src="<?php echo base_url() . "assets/"; ?>exports/jszip.js"></script>
    <script type="text/javascript" src="<?php echo base_url() . "assets/"; ?>exports/jszip-utils.js"></script>
    <script type="text/javascript" src="<?php echo base_url() . "assets/"; ?>exports/FileSaver.js"></script>
    <script type="text/javascript" src="https://canvasjs.com/assets/script/jquery.canvasjs.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.0/jquery-confirm.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.0/jquery-confirm.min.js"></script>
    <!-- Bootstrap Core JavaScript -->
    <script src="<?php echo base_url() . "assets/"; ?>vendors4/popper.js/dist/umd/popper.min.js"></script>
    <script src="<?php echo base_url() . "assets/"; ?>vendors4/bootstrap/dist/js/bootstrap.min.js"></script>

    <!-- Jasny-bootstrap  JavaScript -->
    <script src="<?php echo base_url() . "assets/"; ?>vendors4/jasny-bootstrap/dist/js/jasny-bootstrap.min.js"></script>

    <!-- Slimscroll JavaScript -->
    <script src="<?php echo base_url() . "assets/"; ?>dist/js/jquery.slimscroll.js"></script>

    <!-- Fancy Dropdown JS -->
    <script src="<?php echo base_url() . "assets/"; ?>dist/js/dropdown-bootstrap-extended.js"></script>

    <!-- Tinymce JavaScript -->


    <!-- Tinymce Wysuhtml5 Init JavaScript -->

    <!-- Ion JavaScript -->
    <script src="<?php echo base_url() . "assets/"; ?>vendors4/ion-rangeslider/js/ion.rangeSlider.min.js"></script>
    <script src="<?php echo base_url() . "assets/"; ?>dist/js/rangeslider-data.js"></script>


    <!-- Select2 JavaScript -->
    <script src="<?php echo base_url() . "assets/"; ?>vendors4/select2/dist/js/select2.full.min.js"></script>
    <script src="<?php echo base_url() . "assets/"; ?>dist/js/select2-data.js"></script>

    <!-- Bootstrap Tagsinput JavaScript -->
    <script src="<?php echo base_url() . "assets/"; ?>vendors4/bootstrap-tagsinput/dist/bootstrap-tagsinput.min.html">
    </script>

    <!-- Bootstrap Input spinner JavaScript -->
    <script src="<?php echo base_url() . "assets/"; ?>vendors4/bootstrap-input-spinner/src/bootstrap-input-spinner.js">
    </script>
    <script src="<?php echo base_url() . "assets/"; ?>dist/js/inputspinner-data.js"></script>

    <!-- Pickr JavaScript -->
    <script src="<?php echo base_url() . "assets/"; ?>vendors4/pickr-widget/dist/pickr.min.js"></script>
    <script src="<?php echo base_url() . "assets/"; ?>dist/js/pickr-data.js"></script>

    <!-- Daterangepicker JavaScript -->
    <script src="<?php echo base_url() . "assets/"; ?>vendors4/moment/min/moment.min.js"></script>
    <script src="<?php echo base_url() . "assets/"; ?>vendors4/daterangepicker/daterangepicker.js"></script>
    <script src="<?php echo base_url() . "assets/"; ?>dist/js/daterangepicker-data.js"></script>

    <!-- FeatherIcons JavaScript -->
    <script src="<?php echo base_url() . "assets/"; ?>dist/js/feather.min.js"></script>
    <link href="<?php echo base_url() . "assets/"; ?>/awesomplete.css" rel="stylesheet">
    <script type="text/javascript" src="<?php echo base_url() . "assets/"; ?>/awesomplete.js"></script>

    <!-- Toggles JavaScript -->


    <script src="<?php echo base_url() . "assets/"; ?>vendors4/jquery-toggles/toggles.min.js"></script>
    <script src="<?php echo base_url() . "assets/"; ?>dist/js/toggle-data.js"></script>
    <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">

    <link rel="stylesheet" href="https://weareoutman.github.io/clockpicker/dist/jquery-clockpicker.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/clockpicker/0.0.7/bootstrap-clockpicker.min.js"></script>


    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.js"></script>



    <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/angular.js/1.5.0/angular.min.js"></script>
    <script src="//ajax.googleapis.com/ajax/libs/angularjs/1.6.9/angular-sanitize.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/angular-ui-router/0.3.1/angular-ui-router.min.js"></script>
    <script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/angular.js/1.2.1/angular-route.js"></script>
    <script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/angular.js/1.2.1/angular-resource.js"></script>
    <script src="//cdn.ckeditor.com/4.5.3/full/ckeditor.js"></script>
    <script src="//rawgit.com/lemonde/angular-ckeditor/master/angular-ckeditor.js"></script>
    <script src="https://cdn.rawgit.com/zachsnow/ng-elif/4f9cf12c46dca340de2d784f0a2be5e4bebaf1ff/src/elif.js"></script>

    <script src="//cdnjs.cloudflare.com/ajax/libs/angular-translate/2.18.1/angular-translate.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/angular-translate/2.18.1/angular-translate-loader-static-files/angular-translate-loader-static-files.min.js">
    </script>
    <script src="<?php echo base_url() . "assets/"; ?>app/app.js"></script>

    <script src="<?php echo base_url() . "assets/"; ?>app/data.js"></script>
    <script src="<?php echo base_url() . "assets/"; ?>app/authCtrl.js"></script>
    <script src="<?php echo base_url() . "assets/"; ?>app/directives.js"></script>
    <script src="<?php echo base_url() . "assets/"; ?>app/usermanageCtrl.js"></script>
    <script src="<?php echo base_url() . "assets/"; ?>app/generalsettingCtrl.js"></script>
    <script src="<?php echo base_url() . "assets/"; ?>app/newsCtrl.js"></script>
    <script src="<?php echo base_url() . "assets/"; ?>app/seoCtrl.js"></script>
    <script src="<?php echo base_url() . "assets/"; ?>app/faqCtrl.js"></script>
    <script src="<?php echo base_url() . "assets/"; ?>app/shipmentCtrl.js"></script>
    <script src="<?php echo base_url() . "assets/"; ?>app/drsCtrl.js"></script>
    <script src="<?php echo base_url() . "assets/"; ?>app/drsdetailCtrl.js"></script>
    <script src="<?php echo base_url() . "assets/"; ?>app/manifestmanagementCtrl.js"></script>
    <script src="<?php echo base_url() . "assets/"; ?>app/ofdCtrl.js"></script>
    <script src="<?php echo base_url() . "assets/"; ?>app/pickupmanagementCtrl.js"></script>
    <script src="<?php echo base_url() . "assets/"; ?>app/routemanagementCtrl.js"></script>
    <script src="<?php echo base_url() . "assets/"; ?>app/staffmanagementCtrl.js"></script>
    <script src="<?php echo base_url() . "assets/"; ?>app/branchmanagementCtrl.js"></script>
    <script src="<?php echo base_url() . "assets/"; ?>app/contentservicesCtrl.js"></script>
    <script src="<?php echo base_url() . "assets/"; ?>app/showratingCtrl.js"></script>
    <script src="<?php echo base_url() . "assets/"; ?>app/emailtemplatesCtrl.js"></script>
    <script src="<?php echo base_url() . "assets/"; ?>app/setuserprivilegeCtrl.js"></script>
    <script src="<?php echo base_url() . "assets/"; ?>app/bannermanagementCtrl.js"></script>
    <script src="<?php echo base_url() . "assets/"; ?>app/outsourcemanagementCtrl.js"></script>
    <script src="<?php echo base_url() . "assets/"; ?>app/notificationCtrl.js"></script>
    <script src="<?php echo base_url() . "assets/"; ?>app/customermanagementCtrl.js"></script>
    <script src="<?php echo base_url() . "assets/"; ?>app/couriermanagementCtrl.js"></script>
    <script src="<?php echo base_url() . "assets/"; ?>app/auditCtrl.js"></script>
    <script src="<?php echo base_url() . "assets/"; ?>app/ticketmanagementCtrl.js"></script>
    <script src="<?php echo base_url() . "assets/"; ?>app/operationfilterCtrl.js"></script>
    <script src="<?php echo base_url() . "assets/"; ?>app/amsCtrl.js"></script>
    <script src="<?php echo base_url() . "assets/"; ?>app/inventorymanagementCtrl.js"></script>
    <script src="<?php echo base_url() . "assets/"; ?>app/warehouseCtrl.js"></script>
    <script src="<?php echo base_url() . "assets/"; ?>app/bulkmanagementCtrl.js"></script>
    <script src="<?php echo base_url() . "assets/"; ?>app/feedbackCtrl.js"></script>
    <script src="<?php echo base_url() . "assets/"; ?>app/pickupCtrl.js"></script>
    <script src="<?php echo base_url() . "assets/"; ?>app/cmsserviceCtrl.js"></script>
    <script src="<?php echo base_url() . "assets/"; ?>app/rtomanagementCtrl.js"></script>
    <script src="<?php echo base_url() . "assets/"; ?>app/codmanagementCtrl.js"></script>
    <script src="<?php echo base_url() . "assets/"; ?>app/producttypeCtrl.js"></script>
    <script src="<?php echo base_url() . "assets/"; ?>app/schedulemanagementCtrl.js"></script>
    <script src="<?php echo base_url() . "assets/"; ?>app/zonemanagementCtrl.js"></script>
    <script src="<?php echo base_url() . "assets/"; ?>app/reportCtrl.js"></script>
    <script src="<?php echo base_url() . "assets/"; ?>app/locationCtrl.js"></script>
    <script src="<?php echo base_url() . "assets/"; ?>app/servicesmanagementCtrl.js"></script>
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.5.0/css/all.css">

    <style>
        @media (min-width: 420px) {
            .searchitems {
                width: 95%;
            }

            .hidemanu {
                display: none !important;

            }

            .trackbtn {
                width: 130%;
                height: 100%
            }
        }

        @media (min-width: 576px) {
            .searchitems {
                width: 57%;
            }

            .hidemanu {
                display: none !important;

            }

            .trackbtn {
                width: 161%;
                height: 100%
            }
        }

        /* // Medium devices (tablets, 768px and up) */
        @media (min-width: 768px) {

            .searchitems {
                margin-left: 1%;
                width: 53%;

            }

            .trackbtn {
                width: 177%;
                height: 100%
            }

            .hidemanu {
                display: block !important;

            }

        }

        /* // Large devices (desktops, 992px and up) */
        @media (min-width: 992px) {
            .searchitems {
                width: 42%;
                margin-left: 17%;
            }

            .trackbtn {
                width: 205%;
                height: 100%
            }

            .hidemanu {
                display: block !important;

            }
        }

        /* // X-Large devices (large desktops, 1200px and up) */
        @media (min-width: 1200px) {
            .searchitems {
                width: 42%;
                margin-left: 17%;
            }

            .trackbtn {
                width: 205%;
                height: 100%
            }

            .hidemanu {
                display: block !important;

            }
        }

        /* // XX-Large devices (larger desktops, 1400px and up) */
        @media (min-width: 1400px) {
            .searchitems {
                width: 42%;
                margin-left: 17%;

            }

            .trackbtn {
                width: 205%;
                height: 100%
            }

            .hidemanu {
                display: block !important;

            }
        }
    </style>

    <style>
        .hk-wrapper.hk-vertical-nav .hk-nav.hk-nav-light {
            /* background: #fff; */
            background: <?= getcolor('theme') ?>
        }

        .hk-wrapper .hk-navbar.navbar-dark a.navbar-toggle-btn {
            color: <?= getcolor('font') ?>
        }

        .hk-wrapper.hk-vertical-nav .hk-nav.hk-nav-light .nav-header {
            color: rgba(50, 65, 72, 0.4);
        }

        .hk-wrapper.hk-vertical-nav .hk-nav.hk-nav-light .nav-separator {
            border-color: rgba(50, 65, 72, 0.05);
        }

        .hk-wrapper.hk-vertical-nav .hk-nav.hk-nav-light .navbar-nav .nav-item .nav-link {
            /* color: rgba(50, 65, 72, 0.6); */
            color: <?= getcolor('font') ?>;
        }

        .hk-wrapper.hk-vertical-nav .hk-nav.hk-nav-light .navbar-nav .nav-item .nav-link:hover,
        .hk-wrapper.hk-vertical-nav .hk-nav.hk-nav-light .navbar-nav .nav-item .nav-link:focus {
            color: rgba(50, 65, 72, 0.8);
        }

        .hk-wrapper.hk-vertical-nav .hk-nav.hk-nav-light .navbar-nav .nav-item.active>.nav-link {
            color: #324148;
        }

        .hk-wrapper.hk-vertical-nav .hk-nav.hk-nav-light .hk-nav-close {
            color: #324148;
        }

        /* navbar */
        .hk-wrapper .hk-navbar.navbar-dark {
            /* background: #3894D3; */
            background: <?= getcolor('theme') ?>
        }

        .hk-wrapper .hk-navbar.navbar-dark a.navbar-toggle-btn {
            /* color: #fff; */
            color: <?= getcolor('font') ?>
        }

        .hk-wrapper .hk-navbar.navbar-dark a.nav-link-hover:after {
            background: rgba(255, 255, 255, 0.1);
        }

        .hk-wrapper .hk-navbar.navbar-dark .navbar-nav.hk-navbar-content .nav-item .nav-link {
            /* color: rgba(255, 255, 255, 0.7); */
            color: <?= getcolor('font') ?>
        }

        .hk-wrapper .hk-navbar.navbar-dark .navbar-nav.hk-navbar-content .nav-item .nav-link:hover,
        .hk-wrapper .hk-navbar.navbar-dark .navbar-nav.hk-navbar-content .nav-item .nav-link:focus {
            color: #fff;
        }

        .hk-wrapper .hk-navbar.navbar-dark .navbar-nav.hk-navbar-content .nav-item.dropdown.dropdown-authentication .nav-link .media .media-body>span>i {
            color: rgba(255, 255, 255, 0.4);
        }

        .dropdown-menu {}

        .navbar-expand-xl .navbar-nav .dropdown-menu {
            background: <?= getcolor('theme') ?>;
            color: <?= getcolor('font') ?>;


        }

        .navbar-expand-xl .navbar-nav .dropdown-menu a {
            /* background:<?= getcolor('theme') ?>; */
            color: <?= getcolor('font') ?>;


        }

        .languageopt {
            padding-top: 7px;
            color: <?= getcolor('font') ?>;
        }

        .languageopt ul li:hover {
            /* padding-top: 7px; */
            background-color: white;
            color: black !important;
        }

        .manuoption img {
            color: <?= getcolor('font') ?>;
        }
    </style>

    <style>
        .chat1 {
            margin-top: auto;
            margin-bottom: auto;
        }

        .card1 {
            height: 550px;
            border-radius: 15px !important;
            background-color: <?= getcolor('theme') ?> !important;
            /* background-color: rgba(0, 0, 0, 0.4) !important; */

        }

        .contacts_body {
            padding: 0.75rem 0 !important;
            overflow-y: auto;
            white-space: nowrap;
        }

        .msg_card_body {
            overflow-y: auto;
        }

        .card-header {
            border-radius: 15px 15px 0 0 !important;
            border-bottom: 0 !important;
        }

        .card-footer {
            border-radius: 0 0 15px 15px !important;
            border-top: 0 !important;
        }

        .container {
            align-content: center;
        }

        .search {
            border-radius: 15px 0 0 15px !important;
            background-color: rgba(0, 0, 0, 0.3) !important;
            border: 0 !important;
            /* color: white !important; */
            color: <?= getcolor('font') ?> !important;
        }

        .search:focus {
            box-shadow: none !important;
            outline: 0px !important;
        }

        .type_msg {
            background-color: rgba(0, 0, 0, 0.3) !important;
            border: 0 !important;
            /* color: white !important; */
            color: <?= getcolor('font') ?> !important;
            height: 60px !important;
            overflow-y: auto;
        }

        .type_msg:focus {
            box-shadow: none !important;
            outline: 0px !important;
        }

        .attach_btn {
            border-radius: 15px 0 0 15px !important;
            background-color: rgba(0, 0, 0, 0.3) !important;
            border: 0 !important;
            /* color: white !important; */
            color: <?= getcolor('font') ?> !important;
            cursor: pointer;
        }

        .send_btn {
            border-radius: 0 15px 15px 0 !important;
            background-color: rgba(0, 0, 0, 0.3) !important;
            border: 0 !important;
            color: white !important;
            color: <?= getcolor('font') ?> !important;
            cursor: pointer;
        }

        .search_btn {
            border-radius: 0 15px 15px 0 !important;
            background-color: rgba(0, 0, 0, 0.3) !important;
            border: 0 !important;
            /* color: white !important; */
            color: <?= getcolor('font') ?> !important;
            cursor: pointer;
        }

        .contacts {
            list-style: none;
            padding: 0;
        }

        .contacts li {
            width: 100% !important;
            padding: 5px 10px;
            margin-bottom: 15px !important;
        }

        .active1 {
            background-color: rgba(0, 0, 0, 0.3);
        }

        .user_img {
            height: 61px;
            width: 60px;
            border: 1.5px solid <?= getcolor('font') ?>;


        }

        .user_img_msg {
            height: 40px;
            width: 40px;
            border: 1.5px solid <?= getcolor('font') ?>;

        }

        .img_cont {
            position: relative;
            height: 70px;
            width: 70px;
        }

        .img_cont_msg {
            height: 40px;
            width: 40px;
        }

        .online_icon {
            position: absolute;
            height: 15px;
            width: 15px;
            background-color: #4cd137;
            border-radius: 50%;
            bottom: 0.2em;
            right: 0.4em;
            border: 1.5px solid white;
        }

        .offline {
            background-color: #c23616 !important;
        }

        .user_info {
            margin-top: auto;
            margin-bottom: auto;
            margin-left: 5px;
        }

        .user_info span {
            font-size: 20px;
            /* color: white; */
            color: <?= getcolor('font') ?>;
        }

        .user_info p {
            font-size: 10px;
            /* color: rgba(255, 255, 255, 0.6); */
            color: <?= getcolor('font') ?>;
        }

        .video_cam {
            margin-left: 50px;
            margin-top: 5px;
        }

        .video_cam span {
            color: <?= getcolor('font') ?>;
            font-size: 20px;
            cursor: pointer;
            margin-right: 20px;
        }

        .msg_cotainer {
            margin-top: auto;
            margin-bottom: auto;
            margin-left: 10px;
            border-radius: 25px;
            /* background-color: #82ccdd; */
            /* background-color:<?= getcolor('theme') ?>; */
            color: <?= getcolor('font') ?>;
            background-color: rgba(0, 0, 0, 0.5);
            padding: 10px;
            position: relative;
        }

        .msg_cotainer_send {
            margin-top: auto;
            margin-bottom: auto;
            margin-right: 10px;
            border-radius: 25px;
            /* background-color: #78e08f; */
            background-color: rgba(0, 0, 0, 0.5);
            padding: 10px;
            position: relative;
            color: <?= getcolor('font') ?>;
        }

        .msg_time {
            position: absolute;
            left: 0;
            bottom: -15px;
            /* color: rgba(255, 255, 255, 0.5); */
            color: <?= getcolor('font') ?>;
            font-size: 10px;
            white-space: nowrap;
        }

        .msg_time_send {
            position: absolute;
            right: 0;
            bottom: -15px;
            /* color: rgba(255, 255, 255, 0.5); */
            color: <?= getcolor('font') ?>;
            font-size: 10px;
            white-space: nowrap;
        }

        .msg_head {
            position: relative;
        }

        #action_menu_btn {
            position: absolute;
            right: 10px;
            top: 10px;
            color: white;
            color: <?= getcolor('font') ?>;
            cursor: pointer;
            font-size: 20px;
        }

        .action_menu {
            z-index: 1;
            position: absolute;
            padding: 15px 0;
            background-color: rgba(0, 0, 0, 0.2);
            color: <?= getcolor('font') ?>;
            border-radius: 15px;
            top: 30px;
            right: 15px;
            display: none;
        }

        .action_menu ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .action_menu ul li {
            width: 100%;
            padding: 10px 15px;
            margin-bottom: 5px;
        }

        .action_menu ul li i {
            padding-right: 10px;

        }

        .action_menu ul li:hover {
            cursor: pointer;
            background-color: rgba(0, 0, 0, 0.2);
        }

        .action_menufile {
            z-index: 1;
            position: absolute;
            padding: 15px 0;
            background-color: rgba(0, 0, 0, 0.2);
            color: <?= getcolor('font') ?>;
            border-radius: 15px;
            top: -182px;
            /* display: none; */
        }

        .action_menufile ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .action_menufile ul li {
            width: 100%;
            padding: 10px 15px;
            margin-bottom: 5px;
        }

        .action_menufile ul li i {
            padding-right: 10px;

        }

        .action_menufile ul li:hover {
            cursor: pointer;
            background-color: rgba(0, 0, 0, 0.2);
        }

        .action_menudriver {
            z-index: 1;
            position: absolute;
            padding: 15px 0;
            background-color: rgba(0, 0, 0, 0.2);
            color: <?= getcolor('font') ?>;
            border-radius: 15px;
            top: -182px;
            display: none;
        }

        .action_menudriver ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .action_menudriver ul li {
            width: 100%;
            padding: 10px 15px;
            margin-bottom: 5px;
        }

        .action_menudriver ul li i {
            padding-right: 10px;

        }

        .action_menudriver ul li:hover {
            cursor: pointer;
            background-color: rgba(0, 0, 0, 0.2);
        }

        @media(max-width: 576px) {
            .contacts_card {
                margin-bottom: 15px !important;
            }
        }

        /* width */
        ::-webkit-scrollbar {
            width: 10px;
        }

        /* Track */
        ::-webkit-scrollbar-track {
            box-shadow: inset 0 0 5px grey;
            border-radius: 10px;
        }

        /* Handle */
        ::-webkit-scrollbar-thumb {
            background: #7F7FD5;
            border-radius: 10px;
        }

        /* Handle on hover */
        ::-webkit-scrollbar-thumb:hover {
            background: #5454b6;
        }
    </style>

</head>



<body ng-cloak="" ng-controller="MainCtrl" id="dialog" dir="{{dir}}">
    <!-- class="overlay"-->
    <!--<div class="preloader-it">
        <div class="loader-pendulums"></div>
    </div>-->

    <fullscreen-dialog ng-show="isVisible.loading">
        <div class="dialog">
            <div class="content">
                <div class="loader88"></div>
            </div>
        </div>
    </fullscreen-dialog>
    <?php

    if (empty($this->session->userdata('localSession')['A_USERNAME'])) {
        echo '<div ui-view="loginpage"></div>';
    }

    ?>
    <?php
    if (!empty($this->session->userdata('localSession')['A_USERNAME'])) { ?>
        <div class="hk-wrapper hk-vertical-nav">
            <!-- HK Wrapper -->



            <!-- Top Navbar -->
            <nav class="navbar navbar-expand-xl navbar-dark fixed-top hk-navbar">
                <a id="navbar_toggle_btn" class="navbar-toggle-btn nav-link-hover manuoption" href=""><span class="feather-icon"><i data-feather="menu"></i></span></a>
                <a class="navbar-brand manuoption " href="dashboard">
                    <img class="brand-img d-inline-block" style="" src="<?= site_configTable('logo'); ?>" width="55" alt="brand" />
                    <!-- <img src="assets/logo/ejacklogo.png" class="img-fluid" width="67"> -->
                </a>
                <form class="mr-10 searchitems" style=" display: none;" method="get">
                    <div class="input-group d-inline-flex p-2 bd-highlight">
                        <input class="form-control" type="search" ng-model="searchTrackval" my-enter="GetResultEnterTacking(searchTrackval);" placeholder="AWB Number search I Ref Number Search" aria-label="Search">
                        <div class="input-group-append">
                            <button class="btn formbtn btn-danger mr-2" type="submit" ui-sref="tracking_details({shid:searchTrackval})" value="Track" name="submit">{{'lang_search'|translate}}</button>
                            <a><input type="submit" class="btn formbtn btn-danger trackbtn" id="" ui-sref="tracking_result({shid:searchTrackval})" value="Track" name="submit" style=""></a>

                        </div>

                    </div>

                </form>
                <!--  tracking_result-->
                <ul class="navbar-nav hk-navbar-content">
                    <li></li>
                    <li class="nav-item">

                    </li>
                    <li class="nav-item">
                        &nbsp;
                        <!-- <a id="settings_toggle_btn" class="nav-link nav-link-hover" href="javascript:void(0);"><span class="feather-icon"><i data-feather="settings"></i></span></a> -->
                    </li>
                    <li class="nav-item dropdown dropdown-notifications" id="showsearch">
                        <a class="nav-link dropdown-toggle no-caret" id="searchhowbtn" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="feather-icon"><i id="searchicon" data-feather="search"></i></span>
                            <span class="badge-wrap">
                                <span class="badge badge-primary badge-indicator badge-indicator-sm badge-pill pulse"></span>
                            </span>
                        </a>

                    </li>
                    <li class="nav-item dropdown dropdown-notifications">
                        <a class="nav-link dropdown-toggle no-caret" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="feather-icon"><i data-feather="bell"></i></span><span class="badge-wrap"><span class="badge badge-primary badge-indicator badge-indicator-sm badge-pill pulse"></span></span></a>
                        <div class="dropdown-menu dropdown-menu-right" data-dropdown-in="fadeIn" data-dropdown-out="fadeOut">
                            <h6 class="dropdown-header">Notifications <a href="javascript:void(0);" class="">View all</a>
                            </h6>

                        </div>
                    </li>
                    <li class="nav-item dropdown ">

                        <a class="nav-link dropdown-toggle no-caret" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <div class="media">
                                <!--   <div class="media-img-wrap">
                                <div class="avatar">


                                    <i  class="fa fa-user avatar-img rounded-circle"></i>
                                </div>
                               <span class="badge badge-success badge-indicator"></span>
                            </div>-->
                                <div class="media-body">
                                    <span><i data-feather="user"></i><?php echo $this->session->userdata('localSession')['A_USERNAME']; ?><i class="zmdi zmdi-chevron-down"></i></span>
                                </div>
                            </div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right" data-dropdown-in="flipInX" data-dropdown-out="flipOutX">
                            <a class="dropdown-item" href="my_profile"><i class="dropdown-icon zmdi zmdi-account"></i><span>Profile</span></a>
                            <a class="dropdown-item" href="#"><i class="dropdown-icon zmdi zmdi-card"></i><span>My
                                    balance</span></a>
                            <div class="sub-dropdown-menu show-on-hover">
                                <a href="#" class="dropdown-toggle dropdown-item no-caret"><i class="dropdown-icon zmdi zmdi-email"></i>Inbox</a>
                                <div class="dropdown-menu open-left-side">
                                    <a class="dropdown-item" href="chat"><i class="dropdown-icon zmdi zmdi-accounts "></i><span>Drivers</span></a>
                                    <a class="dropdown-item" href="chatcustomer"><i class="dropdown-icon zmdi zmdi-account "></i><span>Customer</span></a>
                                    <!-- <a class="dropdown-item" href="#"><i class="dropdown-icon zmdi zmdi-minus-circle-outline text-danger"></i><span>Offline</span></a> -->
                                </div>
                            </div>
                            <!-- <a class="dropdown-item" href="inbox.html"><i class="dropdown-icon zmdi zmdi-email"></i><span>Inbox</span></a> -->
                            <a class="dropdown-item" href="#"><i class="dropdown-icon zmdi zmdi-settings"></i><span>Settings</span></a>
                            <div class="dropdown-divider"></div>
                            <div class="sub-dropdown-menu show-on-hover">
                                <a href="#" class="dropdown-toggle dropdown-item no-caret"><i class="zmdi zmdi-check text-success"></i>Online</a>
                                <div class="dropdown-menu open-left-side">
                                    <a class="dropdown-item" href="#"><i class="dropdown-icon zmdi zmdi-check text-success"></i><span>Online</span></a>
                                    <a class="dropdown-item" href="#"><i class="dropdown-icon zmdi zmdi-circle-o text-warning"></i><span>Busy</span></a>
                                    <a class="dropdown-item" href="#"><i class="dropdown-icon zmdi zmdi-minus-circle-outline text-danger"></i><span>Offline</span></a>
                                </div>
                            </div>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" ng-click="logout();" style="cursor:pointer;"><i class="dropdown-icon zmdi zmdi-power"></i><span>Log out</span></a>

                        </div>

                        <!-- <div class="dropdown-menu dropdown-menu-right" data-dropdown-in="flipInX" data-dropdown-out="flipOutX">
                        <a class="dropdown-item"  href="my_profile"><i class="dropdown-icon zmdi zmdi-account"></i><span>Profile</span></a>


                          <div class="dropdown-divider"></div>
                      <div class="sub-dropdown-menu show-on-hover">
                            <a href="#" class="dropdown-toggle dropdown-item no-caret"><i class="zmdi zmdi-check text-success"></i>Online</a>
                            <div class="dropdown-menu open-left-side">
                                <a class="dropdown-item" href="#"><i class="dropdown-icon zmdi zmdi-check text-success"></i><span>Online</span></a>

                            </div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" ng-click="logout();" style="cursor:pointer;"><i class="dropdown-icon zmdi zmdi-power"></i><span>{{'lang_logout'|translate}}</span></a>

                    </div> -->
                        <!-- style="padding-top:7px; color:#cddce3;" -->
                    <li class="dropdown dropdown-user show languageopt">
                        <a class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false" style="cursor:pointer;">
                            EN <i class="caret"></i>
                        </a>

                        <ul class="dropdown-menu dropdown-menu-right">

                            <li><a ng-click="languageChange('ar')" style="cursor:pointer;"><i aria-hidden="true"></i>&nbsp;&nbsp; &nbsp; Arabic</a></li>

                            <li><a ng-click="languageChange('en')" style="cursor:pointer;"><i aria-hidden="true"></i>
                                    &nbsp;&nbsp; &nbsp;English</a></li>

                        </ul>

                    </li>
                    </li>
                </ul>
            </nav>



            <!-- /Top Navbar -->

            <!-- Vertical Nav -->

            <nav class="hk-nav hk-nav-light ">
                <a href="javascript:void(0);" id="hk_nav_close" class="hk-nav-close"><span class="feather-icon"><i data-feather="x"></i></span></a>
                <div class="nicescroll-bar">
                    <div class="navbar-nav-wrap">
                        <ul class="navbar-nav flex-column">
                            <?php if (menuIdExitsInPrivilageArray(1) == 'Y') {
                            ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="javascript:void(0);" data-toggle="collapse" data-target="#gen_setting_drp">
                                        <span class="feather-icon"><i data-feather="settings"></i></span>
                                        <span class="nav-link-text">{{'lang_generalSetting'|translate}}</span>
                                    </a>
                                    <ul id="gen_setting_drp" class="nav flex-column collapse collapse-level-1">
                                        <li class="nav-item">
                                            <ul class="nav flex-column">
                                                <?php if (menuIdExitsInPrivilageArray(46) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="company_details">{{'lang_companyDetails'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(47) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="upload_app">Upload Apps</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(48) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="social_details">Social Details</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(49) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="smtp_configuration">SMTP Configuration</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(50) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="payment_setting">Payment Setting</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(51) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="show_testimonial">Show Testimonial</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(52) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="show_about_us">Show About Us</a>
                                                    </li>
                                                <?php } ?>
                                            </ul>
                                        </li>
                                    </ul>
                                </li>
                            <?php }
                            ?>
                            <?php if (menuIdExitsInPrivilageArray(2) == 'Y') { ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="javascript:void(0);" data-toggle="collapse" data-target="#operation_filter_drp">
                                        <span class="feather-icon"><i data-feather="cpu"></i></span>
                                        <span class="nav-link-text">{{'lang_Operation_Filter'|translate}}</span>
                                    </a>
                                    <ul id="operation_filter_drp" class="nav flex-column collapse collapse-level-1">
                                        <li class="nav-item">
                                            <ul class="nav flex-column">
                                                <?php if (menuIdExitsInPrivilageArray(53) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="ofd_issue">{{'lang_OFD_Issue'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(54) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="order_not_picked">{{'lang_Order_Not_Picked'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(55) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="shipments_hold">{{'lang_Shipment_On_Hold'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(56) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="csa_schedule_issue">{{'lang_CSA_Schedule_Issue'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(57) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="csa_location_issue">{{'lang_CSA_Location_Issue'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(58) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="driver_update">{{'lang_Driver_Not_Update'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(59) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="future_update">{{'lang_Reschedule_for_FutureUpdate'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(60) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="not_dispatch">{{'lang_Schedule_Not_Dispatch'|translate}}</a>
                                                    </li>
                                                <?php } ?>
                                            </ul>
                                        </li>
                                    </ul>
                                </li>
                            <?php }
                            ?>
                            <?php if (menuIdExitsInPrivilageArray(3) == 'Y') {
                            ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="javascript:void(0);" data-toggle="collapse" data-target="#ship_mgmnt_drp">
                                        <span class="feather-icon"><i data-feather="truck"></i></span>
                                        <span class="nav-link-text">{{'lang_shipmentManagement'|translate}}</span>
                                    </a>
                                    <ul id="ship_mgmnt_drp" class="nav flex-column collapse collapse-level-1">
                                        <li class="nav-item">
                                            <ul class="nav flex-column">
                                                <?php if (menuIdExitsInPrivilageArray(61) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="all_shipment">{{'lang_allShipment'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(62) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="archive_shipment">{{'lang_ArchiveShipment'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(63) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="add_new_shipment">Add New Shipment</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(64) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="return_orders">Return Orders</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(65) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="delivered_shipment">Delivered Shipment</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(66) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="deleted_shipment">{{'lang_deletedShipment'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(67) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="scanned_not_listed">In Transit/Scanned not
                                                            Listed</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(68) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="schedule_shipment1">Schedule Shipment</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(68) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="import_new_shipment">Import New Shipment</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(70) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="import_return_shipment">Import Return Shipment</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(71) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="bulk_update">{{'lang_bulkUpdate'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(72) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="assigning_shipments">Assigning Shipments for
                                                            CS</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(73) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="bulk_print">{{'lang_BulkPrint'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(73) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="bulk_track">{{'lang_bulktrack'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(74) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="ready_delivery">{{'lang_Ready_for_Delivery'|translate}}</a>
                                                    </li>
                                                <?php } ?>
                                            </ul>
                                        </li>
                                    </ul>
                                </li>
                            <?php }
                            ?>
                            <?php if (menuIdExitsInPrivilageArray(4) == 'Y') {
                            ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="javascript:void(0);" data-toggle="collapse" data-target="#bulk_drp">
                                        <span class="feather-icon"><i data-feather="file-text"></i></span>
                                        <span class="nav-link-text">{{'lang_Bulk_Invoice_Management'|translate}}</span>
                                    </a>
                                    <ul id="bulk_drp" class="nav flex-column collapse collapse-level-1">
                                        <li class="nav-item">
                                            <ul class="nav flex-column">
                                                <?php if (menuIdExitsInPrivilageArray(75) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="add_bulk_scan">{{'lang_Add_Bulk_Scan'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(76) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="cod_invoices">{{'lang_COD_Invoices'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(77) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="payable_invoices">{{'lang_Payable_Invoice'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(78) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="payable_invoice_cod">{{'lang_Payable_InvoicesCOD'|translate}}</a>
                                                    </li>
                                                <?php } ?>
                                            </ul>
                                        </li>
                                    </ul>
                                </li>
                            <?php }
                            ?>
                            <?php if (menuIdExitsInPrivilageArray(5) == 'Y') {
                            ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="javascript:void(0);" data-toggle="collapse" data-target="#audit_drp">
                                        <span class="feather-icon"><i data-feather="layout"></i></span>
                                        <span class="nav-link-text">{{'lang_Audit'|translate}}</span>
                                    </a>
                                    <ul id="audit_drp" class="nav flex-column collapse collapse-level-1">
                                        <li class="nav-item">
                                            <ul class="nav flex-column">
                                                <?php if (menuIdExitsInPrivilageArray(79) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="operation_audit">{{'lang_OperationAudit'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(80) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="cs_audit">{{'lang_CSAudit'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(81) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="view_audit">{{'lang_ViewAudit'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(82) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="add_reason">{{'lang_AddReason'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(83) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="view_reason">{{'lang_ViewReason'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(84) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="call_record">Call Record</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(85) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="personal_call_record">Personal Call Record</a>
                                                    </li>
                                                <?php } ?>
                                            </ul>
                                        </li>
                                    </ul>
                                </li>
                            <?php }
                            ?>
                            <?php if (menuIdExitsInPrivilageArray(6) == 'Y') { ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="javascript:void(0);" data-toggle="collapse" data-target="#pickup_drp">
                                        <span class="feather-icon"><i data-feather="type"></i></span>
                                        <span class="nav-link-text">Pickup Management</span>
                                    </a>
                                    <ul id="pickup_drp" class="nav flex-column collapse collapse-level-1">
                                        <li class="nav-item">
                                            <ul class="nav flex-column">
                                                <?php if (menuIdExitsInPrivilageArray(86) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="generate_pickup">Generate Pickup</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(87) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="scan_new_pickup">Scan New Pickup</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(88) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="bulk_pickup_update">Bulk Pickup Update</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(89) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="pickup_list">Pickup List</a>
                                                    </li>
                                                <?php } ?>
                                            </ul>
                                        </li>
                                    </ul>
                                </li>
                            <?php };
                            if (menuIdExitsInPrivilageArray(7) == 'Y') {
                            ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="javascript:void(0);" data-toggle="collapse" data-target="#inventory_drp">
                                        <span class="feather-icon"><i data-feather="book-open"></i></span>
                                        <span class="nav-link-text">{{'lang_InventoryManagement'|translate}}</span>
                                    </a>
                                    <ul id="inventory_drp" class="nav flex-column collapse collapse-level-1">
                                        <li class="nav-item">
                                            <ul class="nav flex-column">
                                                <?php if (menuIdExitsInPrivilageArray(90) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="create_location">{{'lang_CreateShelveLocation'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(91) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="edit_location">{{'lang_Manage_Shelve_LocationWH'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(92) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="print_barcode">{{'lang_Print_Shelve_Barcode'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(93) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="add_shelve">{{'lang_AddShelve'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(94) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="show_shelve">{{'lang_ShowShelve'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(95) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="inventory">{{'lang_Inventory'|translate}}</a>
                                                    </li>
                                                <?php } ?>
                                            </ul>
                                        </li>
                                    </ul>
                                </li>
                            <?php }
                            ?>
                            <?php if (menuIdExitsInPrivilageArray(8) == 'Y') {
                            ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="javascript:void(0);" data-toggle="collapse" data-target="#manifest_drp">
                                        <span class="feather-icon"><i data-feather="server"></i></span>
                                        <span class="nav-link-text">{{'lang_Manifest_Management'|translate}}</span>
                                    </a>
                                    <ul id="manifest_drp" class="nav flex-column collapse collapse-level-1">
                                        <li class="nav-item">
                                            <ul class="nav flex-column">
                                                <?php if (menuIdExitsInPrivilageArray(96) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="add_manifest">Add Multi Pieces Manifest</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(97) == 'Y') { ?>

                                                    <li class="nav-item">
                                                        <a class="nav-link" href="show_manifest">{{'lang_Show_Manifest'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(98) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="return_manifest">{{'lang_Return_Manifest'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(99) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="bulk_manifest">Add Manifest</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(100) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="date_update1">{{'lang_Verify_Date_Update'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(101) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="line_haul">{{'lang_Line_Haul'|translate}}</a>
                                                    </li>
                                                <?php } ?>
                                            </ul>
                                        </li>
                                    </ul>
                                </li>
                            <?php }
                            ?>
                            <?php if (menuIdExitsInPrivilageArray(9) == 'Y') {
                            ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="javascript:void(0);" data-toggle="collapse" data-target="#delivery_drp">
                                        <span class="feather-icon"><i data-feather="file-text"></i></span>
                                        <span class="nav-link-text">{{'lang_Delivery_Run_Sheet'|translate}}</span>
                                    </a>
                                    <ul id="delivery_drp" class="nav flex-column collapse collapse-level-1">
                                        <li class="nav-item">
                                            <ul class="nav flex-column">
                                                <?php if (menuIdExitsInPrivilageArray(102) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="add_drs">Add DRS</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(103) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="new_drs">Scan New DRS</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(104) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="show_drs">{{'lang_Show_DRS'|translate}}</a>
                                                    </li>
                                                <?php } ?>
                                            </ul>
                                        </li>
                                    </ul>
                                </li>
                            <?php }
                            ?>
                            <?php if (menuIdExitsInPrivilageArray(10) == 'Y') {
                            ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="javascript:void(0);" data-toggle="collapse" data-target="#warehouse_drp">
                                        <span class="feather-icon"><i data-feather="home"></i></span>
                                        <span class="nav-link-text">{{'lang_WHManagement'|translate}}</span>
                                    </a>
                                    <ul id="warehouse_drp" class="nav flex-column collapse collapse-level-1">
                                        <li class="nav-item">
                                            <ul class="nav flex-column">
                                                <?php if (menuIdExitsInPrivilageArray(105) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="scan_shipment">{{'lang_ScanShipment'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(106) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="hold_shipment">{{'lang_ScanOnHold_Shipment'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(107) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="schedule_shipment">{{'lang_ScanScheduleShipment'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(108) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="bound_shipment">{{'lang_ScanInBoundShipment'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(109) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="inventory_report">{{'lang_Show_Inventory_Report'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(110) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="security_check">{{'lang_SecurityCheck'|translate}}</a>
                                                    </li>
                                                <?php } ?>
                                            </ul>
                                        </li>
                                    </ul>
                                </li>
                            <?php }
                            ?>
                            <?php if (menuIdExitsInPrivilageArray(11) == 'Y') {
                            ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="javascript:void(0);" data-toggle="collapse" data-target="#routs_drp">
                                        <span class="feather-icon"><i data-feather="map"></i></span>
                                        <span class="nav-link-text">{{'lang_RouteManagement'|translate}}</span>
                                    </a>
                                    <ul id="routs_drp" class="nav flex-column collapse collapse-level-1">
                                        <li class="nav-item">
                                            <ul class="nav flex-column">
                                                <?php if (menuIdExitsInPrivilageArray(111) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="add_route">{{'lang_AddRoute'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(112) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="show_route">{{'lang_ShowRoute'|translate}}</a>
                                                    </li>
                                                <?php } ?>
                                            </ul>
                                        </li>
                                    </ul>
                                </li>
                            <?php }
                            ?>
                            <?php if (menuIdExitsInPrivilageArray(12) == 'Y') {
                            ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="javascript:void(0);" data-toggle="collapse" data-target="#servi_drp">
                                        <span class="feather-icon"><i data-feather="shopping-cart"></i></span>
                                        <span class="nav-link-text">{{'lang_ServiceManagement'|translate}}</span>
                                    </a>
                                    <ul id="servi_drp" class="nav flex-column collapse collapse-level-1">
                                        <li class="nav-item">
                                            <ul class="nav flex-column">
                                                <?php if (menuIdExitsInPrivilageArray(113) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="add_services">{{'lang_Add_Services'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(114) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="view_services">{{'lang_ViewServices'|translate}}</a>
                                                    </li>
                                                <?php } ?>
                                            </ul>
                                        </li>
                                    </ul>
                                </li>
                            <?php }
                            if (menuIdExitsInPrivilageArray(13) == 'Y') {
                            ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="javascript:void(0);" data-toggle="collapse" data-target="#user_menu_drop">
                                        <span class="feather-icon"><i data-feather="users"></i></span>
                                        <span class="nav-link-text">User management</span>
                                    </a>
                                    <ul id="user_menu_drop" class="nav flex-column collapse collapse-level-1">
                                        <li class="nav-item">
                                            <ul class="nav flex-column">
                                                <?php if (menuIdExitsInPrivilageArray(115) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" ui-sref="show_user">Show User</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(116) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" ui-sref="Add_agent">Add User</a>
                                                    </li>
                                                <?php } ?>
                                            </ul>
                                        </li>
                                    </ul>
                                </li>
                            <?php }
                            if (menuIdExitsInPrivilageArray(14) == 'Y') {
                            ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="javascript:void(0);" data-toggle="collapse" data-target="#staff_drop">
                                        <span class="feather-icon"><i data-feather="users"></i></span>
                                        <span class="nav-link-text">{{'lang_StaffManagement'|translate}}</span>
                                    </a>
                                    <ul id="staff_drop" class="nav flex-column collapse collapse-level-1">
                                        <li class="nav-item">
                                            <ul class="nav flex-column">
                                                <?php if (menuIdExitsInPrivilageArray(117) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="add_staff">{{'lang_AddStaff'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(118) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="show_staff">{{'lang_showStaff'|translate}}</a>
                                                    </li>
                                                <?php } ?>
                                            </ul>
                                        </li>
                                    </ul>
                                </li>
                            <?php }
                            ?>
                            <?php if (menuIdExitsInPrivilageArray(15) == 'Y') {
                            ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="javascript:void(0);" data-toggle="collapse" data-target="#customer_drop">
                                        <span class="feather-icon"><i data-feather="users"></i></span>
                                        <span class="nav-link-text">{{'lang_customerManagement'|translate}}</span>
                                    </a>
                                    <ul id="customer_drop" class="nav flex-column collapse collapse-level-1">
                                        <li class="nav-item">
                                            <ul class="nav flex-column">
                                                <?php if (menuIdExitsInPrivilageArray(119) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="add_customer">{{'lang_addCustomer'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(120) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="show_customer">{{'lang_ShowCustomer'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(121) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="import_rates">{{'lang_ImportRates'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(122) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="account_verification">{{'lang_AccountVerification'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(122) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="privialge_customer">{{'lang_setcustometprivalage'|translate}}</a>
                                                    </li>
                                                <?php } ?>
                                            </ul>
                                        </li>
                                    </ul>
                                </li>
                            <?php }
                            ?>
                            <?php if (menuIdExitsInPrivilageArray(16) == 'Y') {
                            ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="javascript:void(0);" data-toggle="collapse" data-target="#courier_drop">
                                        <span class="feather-icon"><i data-feather="truck"></i></span>
                                        <span class="nav-link-text">{{'lang_CouriersManagement'|translate}}</span>
                                    </a>
                                    <ul id="courier_drop" class="nav flex-column collapse collapse-level-1">
                                        <li class="nav-item">
                                            <ul class="nav flex-column">
                                                <?php if (menuIdExitsInPrivilageArray(123) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="add_couriers">{{'lang_Add_Couriers'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(124) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="show_couriers">{{'lang_Show_Couriers'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(125) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="odometer_details">{{'lang_Odometer_Details'|translate}}</a>
                                                    </li>
                                                <?php } ?>
                                            </ul>
                                        </li>
                                    </ul>
                                </li>
                            <?php }
                            if (menuIdExitsInPrivilageArray(17) == 'Y') {
                            ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="javascript:void(0);" data-toggle="collapse" data-target="#branch_drop">
                                        <span class="feather-icon"><i data-feather="users"></i></span>
                                        <span class="nav-link-text">Branch Management</span>
                                    </a>
                                    <ul id="branch_drop" class="nav flex-column collapse collapse-level-1">
                                        <li class="nav-item">
                                            <ul class="nav flex-column">
                                                <?php if (menuIdExitsInPrivilageArray(126) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="add_branch">Add New Branch</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(127) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="show_branch">Show Branch</a>
                                                    </li>
                                                <?php } ?>
                                            </ul>
                                        </li>
                                    </ul>
                                </li>
                            <?php }
                            if (menuIdExitsInPrivilageArray(18) == 'Y') {
                            ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="javascript:void(0);" data-toggle="collapse" data-target="#reports_drop">
                                        <span class="feather-icon"><i data-feather="layers"></i></span>
                                        <span class="nav-link-text">{{'lang_reports'|translate}}</span>
                                    </a>
                                    <ul id="reports_drop" class="nav flex-column collapse collapse-level-1">
                                        <li class="nav-item">
                                            <ul class="nav flex-column">
                                                <?php if (menuIdExitsInPrivilageArray(128) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="supplier_report">Supplier Report</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(129) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="shipment_report">Shipment Report</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(130) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="transaction_report">{{'lang_TransactionReport'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(131) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="payment_report">{{'lang_Payment_Report'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(132) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="client_report">{{'lang_Sales_By_Client_Report'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(133) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="hold_report">{{'lang_OnHoldReport'|translate}}</a>
                                                    </li>
                                                <?php } ?>
                                            </ul>
                                        </li>
                                    </ul>
                                </li>
                            <?php }
                            ?>
                            <?php if (menuIdExitsInPrivilageArray(19) == 'Y') {
                            ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="javascript:void(0);" data-toggle="collapse" data-target="#rto_drop">
                                        <span class="feather-icon"><i data-feather="users"></i></span>
                                        <span class="nav-link-text">{{'lang_RTOManagement'|translate}}</span>
                                    </a>
                                    <ul id="rto_drop" class="nav flex-column collapse collapse-level-1">
                                        <li class="nav-item">
                                            <ul class="nav flex-column">
                                                <?php if (menuIdExitsInPrivilageArray(134) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="rto_list">RTO List</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(135) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="pending_list">{{'lang_Pending_RTO_List'|translate}}</a>
                                                    </li>
                                                <?php } ?>
                                            </ul>
                                        </li>
                                    </ul>
                                </li>
                            <?php }
                            ?>
                            <?php if (menuIdExitsInPrivilageArray(20) == 'Y') {
                            ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="javascript:void(0);" data-toggle="collapse" data-target="#cod_drop">
                                        <span class="feather-icon"><i data-feather="dollar-sign"></i></span>
                                        <span class="nav-link-text">{{'lang_COD_management'|translate}}</span>
                                    </a>
                                    <ul id="cod_drop" class="nav flex-column collapse collapse-level-1">
                                        <li class="nav-item">
                                            <ul class="nav flex-column">
                                                <?php if (menuIdExitsInPrivilageArray(136) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="confirm_shipment">{{'lang_Confirm_COD_Shipment'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(137) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="pending_shipment">{{'lang_Pending_COD_Shipment'|translate}}</a>
                                                    </li>
                                                <?php } ?>
                                            </ul>
                                        </li>
                                    </ul>
                                </li>
                            <?php }
                            if (menuIdExitsInPrivilageArray(21) == 'Y') {
                            ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="javascript:void(0);" data-toggle="collapse" data-target="#coupon_drop">
                                        <span class="feather-icon"><i data-feather="users"></i></span>
                                        <span class="nav-link-text">Coupon Management</span>
                                    </a>
                                    <ul id="coupon_drop" class="nav flex-column collapse collapse-level-1">
                                        <li class="nav-item">
                                            <ul class="nav flex-column">
                                                <?php if (menuIdExitsInPrivilageArray(138) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="new_coupon">Generate New Coupon</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(139) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="valid_coupon">Valid Coupon</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(140) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="expire_coupon">Expire Coupon</a>
                                                    </li>
                                                <?php } ?>
                                            </ul>
                                        </li>
                                    </ul>
                                </li>
                            <?php }
                            if (menuIdExitsInPrivilageArray(22) == 'Y') {
                            ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="ofd_report">
                                        <span class="feather-icon"><i data-feather="book"></i></span>
                                        <span class="nav-link-text">{{'lang_OFDrepor'|translate}}</span>
                                    </a>
                                </li>
                            <?php }
                            if (menuIdExitsInPrivilageArray(23) == 'Y') {
                            ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="payment_details">
                                        <span class="feather-icon"><i data-feather="book"></i></span>
                                        <span class="nav-link-text">Payments Details</span>
                                    </a>
                                </li>
                            <?php }
                            if (menuIdExitsInPrivilageArray(24) == 'Y') { ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="javascript:void(0);" data-toggle="collapse" data-target="#inv_drop">
                                        <span class="feather-icon"><i data-feather="users"></i></span>
                                        <span class="nav-link-text">Invoice Management</span>
                                    </a>
                                    <ul id="inv_drop" class="nav flex-column collapse collapse-level-1">
                                        <li class="nav-item">
                                            <ul class="nav flex-column">
                                                <?php if (menuIdExitsInPrivilageArray(141) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="invoice_management">New Invoice Managment</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(142) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="cod_report">COD Report</a>
                                                    </li>
                                                <?php } ?>
                                            </ul>
                                        </li>
                                    </ul>
                                </li>
                            <?php }
                            if (menuIdExitsInPrivilageArray(24) == 'Y') { ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="javascript:void(0);" data-toggle="collapse" data-target="#pro_drop">
                                        <span class="feather-icon"><i data-feather="users"></i></span>
                                        <span class="nav-link-text">Product Type</span>
                                    </a>
                                    <ul id="pro_drop" class="nav flex-column collapse collapse-level-1">
                                        <li class="nav-item">
                                            <ul class="nav flex-column">
                                                <?php if (menuIdExitsInPrivilageArray(143) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="add_product_type">Add Product Type</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(144) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="show_product_type">Show Product Type</a>
                                                    </li>
                                                <?php } ?>
                                            </ul>
                                        </li>
                                    </ul>
                                </li>
                            <?php }
                            if (menuIdExitsInPrivilageArray(26) == 'Y') {
                            ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="javascript:void(0);" data-toggle="collapse" data-target="#zones">
                                        <span class="feather-icon"><i data-feather="compass"></i></span>
                                        <span class="nav-link-text">{{'lang_zoneManagement'|translate}}</span>
                                    </a>
                                    <ul id="zones" class="nav flex-column collapse collapse-level-1">
                                        <li class="nav-item">
                                            <ul class="nav flex-column">
                                                <?php if (menuIdExitsInPrivilageArray(145) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="zone_list">{{'lang_Show_Zone_List'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(146) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="city_zone">{{'lang_Set_City_Zone'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(147) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="country_zone">{{'lang_Set_Country_Zone'|translate}}</a>
                                                    </li>
                                                <?php } ?>
                                            </ul>
                                        </li>
                                    </ul>
                                </li>

                            <?php }
                            ?>
                            <?php if (menuIdExitsInPrivilageArray(27) == 'Y') {
                            ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="javascript:void(0);" data-toggle="collapse" data-target="#locate">
                                        <span class="feather-icon"><i data-feather="map-pin"></i></span>
                                        <span class="nav-link-text">{{'lang_Location_Management'|translate}}</span>
                                    </a>
                                    <ul id="locate" class="nav flex-column collapse collapse-level-1">
                                        <li class="nav-item">
                                            <ul class="nav flex-column">
                                                <?php if (menuIdExitsInPrivilageArray(148) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="add_city">{{'lang_Add_City'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(149) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="location_list">{{'lang_Location_List'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(150) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="import_hub">{{'lang_Import_Hub'|translate}}</a>
                                                    </li>
                                                <?php } ?>
                                            </ul>
                                        </li>
                                    </ul>
                                </li>
                            <?php }
                            if (menuIdExitsInPrivilageArray(28) == 'Y') {
                            ?>

                                <li class="nav-item">
                                    <a class="nav-link" href="content_services">
                                        <span class="feather-icon"><i data-feather="book"></i></span>
                                        <span class="nav-link-text">Content Services</span>
                                    </a>
                                </li>
                            <?php }
                            if (menuIdExitsInPrivilageArray(29) == 'Y') { ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="cms_pages">
                                        <span class="feather-icon"><i data-feather="book"></i></span>
                                        <span class="nav-link-text">CMS Pages</span>
                                    </a>
                                </li>
                            <?php }
                            if (menuIdExitsInPrivilageArray(30) == 'Y') {
                            ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="tickets">
                                        <span class="feather-icon"><i data-feather="clipboard"></i></span>
                                        <span class="nav-link-text">{{'lang_Tickets'|translate}}</span>
                                    </a>
                                </li>
                            <?php }
                            ?>
                            <?php if (menuIdExitsInPrivilageArray(31) == 'Y') {
                            ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="newfeedback">
                                        <span class="feather-icon"><i data-feather="book"></i></span>
                                        <span class="nav-link-text">{{'lang_Feedback'|translate}}</span>
                                    </a>
                                </li>
                            <?php }
                            ?>
                            <?php if (menuIdExitsInPrivilageArray(32) == 'Y') {
                            ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="showrating">
                                        <span class="feather-icon"><i data-feather="activity"></i></span>
                                        <span class="nav-link-text">{{'lang_Show_Rating'|translate}}</span>
                                    </a>
                                </li>
                            <?php }
                            if (menuIdExitsInPrivilageArray(33) == 'Y') {
                            ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="javascript:void(0);" data-toggle="collapse" data-target="#news_drop">
                                        <span class="feather-icon"><i data-feather="users"></i></span>
                                        <span class="nav-link-text">News</span>
                                    </a>
                                    <ul id="news_drop" class="nav flex-column collapse collapse-level-1">
                                        <li class="nav-item">
                                            <ul class="nav flex-column">
                                                <?php if (menuIdExitsInPrivilageArray(151) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="add_news">Add News</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(152) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="show_news">Show News</a>
                                                    </li>
                                                <?php } ?>
                                            </ul>
                                        </li>
                                    </ul>
                                </li>
                            <?php }
                            if (menuIdExitsInPrivilageArray(34) == 'Y') {
                            ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="javascript:void(0);" data-toggle="collapse" data-target="#notifi_drop">
                                        <span class="feather-icon"><i data-feather="users"></i></span>
                                        <span class="nav-link-text">{{'lang_Notification'|translate}}</span>
                                    </a>
                                    <ul id="notifi_drop" class="nav flex-column collapse collapse-level-1">
                                        <li class="nav-item">
                                            <ul class="nav flex-column">
                                                <?php if (menuIdExitsInPrivilageArray(153) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="add_notification">{{'lang_Add_Notification'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(154) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="show_notification">{{'lang_Show_Notification'|translate}}</a>
                                                    </li>
                                                <?php } ?>
                                            </ul>
                                        </li>
                                    </ul>
                                </li>
                            <?php }
                            if (menuIdExitsInPrivilageArray(35) == 'Y') {
                            ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="javascript:void(0);" data-toggle="collapse" data-target="#pick_drop">
                                        <span class="feather-icon"><i data-feather="users"></i></span>
                                        <span class="nav-link-text">Pickup Location</span>
                                    </a>
                                    <ul id="pick_drop" class="nav flex-column collapse collapse-level-1">
                                        <li class="nav-item">
                                            <ul class="nav flex-column">
                                                <?php if (menuIdExitsInPrivilageArray(155) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="show_location">Show Pickup Location</a>
                                                    </li>
                                                <?php } ?>
                                            </ul>
                                        </li>
                                    </ul>
                                </li>
                            <?php }
                            if (menuIdExitsInPrivilageArray(36) == 'Y') {
                            ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="javascript:void(0);" data-toggle="collapse" data-target="#email_drop">
                                        <span class="feather-icon"><i data-feather="at-sign"></i></span>
                                        <span class="nav-link-text">{{'lang_Email_Management'|translate}}</span>
                                    </a>
                                    <ul id="email_drop" class="nav flex-column collapse collapse-level-1">
                                        <li class="nav-item">
                                            <ul class="nav flex-column">
                                                <?php if (menuIdExitsInPrivilageArray(156) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="email_setting">{{'lang_Email_Templates'|translate}}</a>
                                                    </li>
                                                <?php } ?>
                                            </ul>
                                        </li>
                                    </ul>
                                </li>
                            <?php }
                            if (menuIdExitsInPrivilageArray(37) == 'Y') {
                            ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="featured_partners">
                                        <span class="feather-icon"><i data-feather="book"></i></span>
                                        <span class="nav-link-text">Featured Partners</span>
                                    </a>
                                </li>
                            <?php }
                            if (menuIdExitsInPrivilageArray(38) == 'Y') {
                            ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="seo">
                                        <span class="feather-icon"><i data-feather="book"></i></span>
                                        <span class="nav-link-text">{{'lang_SEO'|translate}}</span>
                                    </a>
                                </li>
                            <?php }
                            if (menuIdExitsInPrivilageArray(39) == 'Y') {
                            ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="category_list">
                                        <span class="feather-icon"><i data-feather="book"></i></span>
                                        <span class="nav-link-text">Product Category List</span>
                                    </a>
                                </li>
                            <?php }
                            if ($this->session->userdata('adminusertype') == 'A' || menuIdExitsInPrivilageArray(40) == 'Y') {
                            ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="user_privilege">
                                        <span class="feather-icon"><i data-feather="book"></i></span>
                                        <span class="nav-link-text">{{'lang_setUserPrivilege'|translate}}</span>
                                    </a>
                                </li>
                            <?php }
                            if (menuIdExitsInPrivilageArray(41) == 'Y') {
                            ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="javascript:void(0);" data-toggle="collapse" data-target="#banner_drop">
                                        <span class="feather-icon"><i data-feather="users"></i></span>
                                        <span class="nav-link-text">Home Banner Management</span>
                                    </a>
                                    <ul id="banner_drop" class="nav flex-column collapse collapse-level-1">
                                        <li class="nav-item">
                                            <ul class="nav flex-column">
                                                <?php if (menuIdExitsInPrivilageArray(157) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="show_banner">Show Banner</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(158) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="add_banner">Add Banner</a>
                                                    </li>
                                                <?php } ?>
                                            </ul>
                                        </li>
                                    </ul>
                                </li>
                            <?php }
                            if (menuIdExitsInPrivilageArray(42) == 'Y') {
                            ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="javascript:void(0);" data-toggle="collapse" data-target="#out_drop">
                                        <span class="feather-icon"><i data-feather="user-plus"></i></span>
                                        <span class="nav-link-text">{{'lang_OutsourceManagement'|translate}}</span>
                                    </a>
                                    <ul id="out_drop" class="nav flex-column collapse collapse-level-1">
                                        <li class="nav-item">
                                            <ul class="nav flex-column">
                                                <?php if (menuIdExitsInPrivilageArray(159) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="add_supplier">{{'lang_AddSupplier'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(160) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="show_supplier">{{'lang_ShowSupplier'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(161) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="generate_voice">{{'lang_Generate_In_Voice'|translate}}</a>
                                                    </li>
                                                <?php } ?>

                                            </ul>
                                        </li>
                                    </ul>
                                </li>
                            <?php }
                            if (menuIdExitsInPrivilageArray(43) == 'Y') {
                            ?>

                                <li class="nav-item">
                                    <a class="nav-link" href="javascript:void(0);" data-toggle="collapse" data-target="#ams_drop">
                                        <span class="feather-icon"><i data-feather="users"></i></span>
                                        <span class="nav-link-text">AMS(Address Management System)</span>
                                    </a>
                                    <ul id="ams_drop" class="nav flex-column collapse collapse-level-1">
                                        <li class="nav-item">
                                            <ul class="nav flex-column">
                                                <?php if (menuIdExitsInPrivilageArray(162) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="show_address">Show Address</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(163) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="shipment_address">Shipment Address</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(164) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="new_address">Add New Address</a>
                                                    </li>
                                                <?php } ?>
                                            </ul>
                                        </li>
                                    </ul>
                                </li>
                            <?php }
                            if (menuIdExitsInPrivilageArray(44) == 'Y') {
                            ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="javascript:void(0);" data-toggle="collapse" data-target="#schedule_drop">
                                        <span class="feather-icon"><i data-feather="users"></i></span>
                                        <span class="nav-link-text">{{'lang_ScheduleManagement'|translate}}</span>
                                    </a>
                                    <ul id="schedule_drop" class="nav flex-column collapse collapse-level-1">
                                        <li class="nav-item">
                                            <ul class="nav flex-column">
                                                <?php if (menuIdExitsInPrivilageArray(165) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="cs_schedule">{{'lang_CSSchedule'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(166) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="blind_schedule">{{'lang_BlindSchedule'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(167) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="bulk_reschedule">{{'lang_BulkReschedule'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(168) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="schedule_remove">{{'lang_BulkScheduleRemove'|translate}}</a>
                                                    </li>
                                                <?php } ?>

                                                <li class="nav-item">
                                                    <a class="nav-link" href="bulk_hold">{{'lang_BulkOnhold'|translate}}</a>
                                                </li>

                                                <?php if (menuIdExitsInPrivilageArray(169) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="date_update1">{{'lang_Verify_Date_Update'|translate}}</a>
                                                    </li>
                                                <?php }
                                                if (menuIdExitsInPrivilageArray(170) == 'Y') { ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="pending_schedule">Pending Schedule</a>
                                                    </li>
                                                <?php } ?>
                                            </ul>
                                        </li>
                                    </ul>
                                </li>
                            <?php }
                            ?>
                            <?php if (menuIdExitsInPrivilageArray(45) == 'Y') {
                            ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="javascript:void(0);" data-toggle="collapse" data-target="#faq_drop">
                                        <span class="feather-icon"><i data-feather="users"></i></span>
                                        <span class="nav-link-text">{{'lang_FAQ'|translate}}</span>
                                    </a>
                                    <ul id="faq_drop" class="nav flex-column collapse collapse-level-1">
                                        <li class="nav-item">
                                            <ul class="nav flex-column">
                                                <li class="nav-item">
                                                    <a class="nav-link" href="add_faq">{{'lang_Add_FAQ'|translate}}</a>
                                                </li>
                                                <li class="nav-item">
                                                    <a class="nav-link" href="show_faq">{{'lang_Show_FAQ'|translate}}</a>
                                                </li>
                                            </ul>
                                        </li>
                                    </ul>
                                </li>
                            <?php }
                            ?>

                        </ul>
                        <hr class="nav-separator">
                    </div>
                </div>
            </nav>


            <div id="hk_nav_backdrop" class="hk-nav-backdrop"></div>
            <!-- /Vertical Nav -->

            <div class="hk-pg-wrapper">

                <div ui-view="content"></div>

                <div ui-view="footer" align="right"></div>

            </div>

        </div>
    <?php } ?>
    <?php print_r($privilage); // $val=array_search("5", $privilage);
    ?>
    <toaster-container toaster-options="{'time-out': 500}"></toaster-container>
    <script src="<?php echo base_url() . "assets/"; ?>dist/js/init.js"></script>
    <script src="<?php echo base_url() . "assets/"; ?>dist/js/dashboard-data.js"></script>
    <script>
        $(document).ready(function() {
            $('#searchhowbtn').click(function(e) {

                e.preventDefault();
                $(".searchitems").fadeToggle();
                $('.manuoption').toggleClass('hidemanu');




            });

            $('#action_menu_btn').click(function() {
                $('.action_menu').toggle();
            });
            $('#action_menu_filebtn').click(function() {
                $('.action_menufile').toggle();
            });
            $('#actoin_menudriverbtn').click(function(e) {
                e.preventDefault();
                $(".action_menudriver").toggle();

            });
        });
    </script>
</body>

</html>