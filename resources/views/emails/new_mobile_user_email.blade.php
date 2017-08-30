<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
    </head>
    <body>
            /*!important! Replaced by a notification*/

	<div>
		<p>Hi {{$user->first_name}} {{$user->last_name}},</p>
		
		<p>You are receiving this email because you have been registered to use OdinLite Mobile App by your employer.</p>
		
		<p>OdinLite is available for download via the App Store and the Play Store.</p>
		
		<p>Please create a new password for use when logging into OdinLite</p>
		<div style="padding-top: 10px; padding-bottom: 10px;">
		<a href="http://odinlite.com/public/password/reset" 
		   style="font-family: Arial,'Helvetica Neue',Helvetica,sans-serif;
    display: block;
    display: inline-block;
    width: 200px;
    min-height: 20px;
    padding: 10px;
    background-color: #3869D4;
    border-radius: 3px;
    color: #ffffff;
    font-size: 15px;
    line-height: 25px;
    text-align: center;
    text-decoration: none;
    background-color: #3869D4;"
		>
		Create Password
		</a>
                </div>
		
		<p>Regards,</p>
		
		<p>Odin</p>
		
		<p>PS Please be sure to add admin@odinlite.com to your safe sender&#39;s list to ensure future communication lands in your inbox.</p>
	</div>
    </body>
</html>