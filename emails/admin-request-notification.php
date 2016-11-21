<!DOCTYPE html>
<html>
<head>
    <style type="text/css">
        /* Client-specific Styles */
        #outlook a{padding:0;}
        /* Force Outlook to provide a "view in browser" button. */
        body{width:100% !important;}
        .ReadMsgBody{width:100%;}
        .ExternalClass{width:100%;}
        /* Force Hotmail to display emails at full width */
        body{-webkit-text-size-adjust:none;}
        /* Prevent Webkit platforms from changing default text sizes. */

        /* Reset Styles */
        body {margin:0;padding:0;}
        img {border:0;height:auto;line-height:100%;outline:none;text-decoration:none;}
        table td{border-collapse:collapse;}

        body {font-family: Helvetica Neue, Arial, Helvetica, sans-serif !important;-webkit-text-size-adjust: none !important;}
        .content {color: #2e2e2e;font-size: 16px;}
        .content p {margin: 0 0 20px;}
        .content p:last-child {margin-bottom: 0;}
        a:visited {color: #366AA1 !important;}
        a:hover {color: #29517b !important;}
        img {border-style: none !important;}
        #facebook a:visited {color: #777777 !important;}
        #facebook a:hover {color: #777777 !important;}
        @media only screen and (max-device-width: 480px) {
            .fixed-width {width: 325px !important;}
            .table {padding: 0 !important;}
        }
    </style>
    <title></title>
</head>

<body style="font-family: Helvetica Neue, Arial, Helvetica, sans-serif; -webkit-text-size-adjust: none;" leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0">
	<h3>A new user has applied to be a distributor on the Fuel3D Distributors' Portal</h3>
	<p>
		First name: <?=htmlentities($first_name)?><br>
		Surname: <?=htmlentities($last_name)?><br>
		Email: <?=htmlentities($email)?><br>
		Country: <?=htmlentities($country)?><br>
		Why does he want to be a distributor: <?=htmlentities($why)?>
	</p>
	<p>
		<a href="<?=admin_url('users.php')?>?page=users-user-role-editor.php&object=user&user_id=<?=$user_id?>">
			Click here to activate this user inside the WordPress Dashboard
		</a> (You just need to set the Role to &quot;Distributor&quot;)
	</p>
</body>
</html>