<?php
    // form processing
    if(isset($_POST['submit'])){
    	// basic init
        require_once "includes/functions.php";
	
    	// user input
    	$name = $_POST['frm_name'];
    	$mail = $_POST['frm_email'];
    	$subject = $_POST['frm_subject'];
    	$comments = $_POST['frm_comments'];
	
    	// validate input
    	$error = array();
    	if(empty($name)) $error[1] = true;
	
    	if(empty($mail)) $error[2] = true;
    	if(!check_email_address($mail)) $error[2] = true;
	
    	if(empty($subject)) $error[3] = true;
	
    	if(empty($comments)) $error[4] = true;
	
    	if(empty($error)){
    		mail("support@trakkapp.com", "Trakk: '" . $subject . "'",
    			"Name: \t\t\t" . $name . "\n\nEmail:\t\t\t". $mail ."\n\nMessage: \n" . stripslashes($comments) . "\n\n",
    			"From: noreply@trakkapp.com\r\nReply-To: $mail\r\n");
		
    		$show_succes_msg = true;
    		unset($_POST);
    	}
    }

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta http-equiv="imagetoolbar" content="no" />

    <title>Trakk › Contact</title>
    
    <link rel="stylesheet" type="text/css" media="screen" href="stylesheets/main.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="stylesheets/fancybox.css" />
        
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js" type="text/javascript"></script>
    <script src="javascripts/jquery.embedquicktime.js" type="text/javascript" charset="utf-8"></script>
    <script src="javascripts/jquery.fancybox.js" type="text/javascript" charset="utf-8"></script>
    <script src="javascripts/jquery.easing.1.3.js" type="text/javascript" charset="utf-8"></script>
    
    <script type="text/javascript">
        $(document).ready(function() {
            jQuery.embedquicktime({
                jquery: 'http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js', 
                plugin: 'javascripts/jquery.embedquicktime.js'
            });
        });
        $(document).ready(function() {
            $("a.link").fancybox({
                'zoomOpacity'           : true,
                'zoomSpeedIn'           : 300,
                'zoomSpeedOut'          : 300,
            	});
        });
    </script>
    <!--[if IE 6]>
        <link rel="stylesheet" type="text/css" media="screen" href="stylesheets/main-IE6.css" />        
        <script src="javascripts/DD_belatedPNG.js"></script>
        <script>
          /* EXAMPLE */
          DD_belatedPNG.fix('#iphone, #iphone a, h1, .link .img');

          /* string argument can be any CSS selector */
          /* change it to what suits you! */
        </script>
    <![endif]-->

</head>
<body>
    <div id="wrapper">
        <div id="header">
            <ul id="navigation">
                <li><a href="index.html"><span>Home</span></a></li>
                <li><a href="about.html"><span>About</span></a></li>
                <li class="current"><a href="contact.php"><span>Contact</span></a></li>
            </ul>
            <div id="title">
                <h1>Trakk</h1>
                <a href="#"><span>Free for a limited time</span></a>
            </div>
        </div>
        <div id="main">
            <div id="iphone">
                <div class="img">
                    <div class="hvlog {width: '230', height: '346'}">
                        <img src="media/screenshot-1.png" alt="" />
                    </div>
                </div>
            </div>
            <form id="content" action="contact.php" method="post" accept-charset="utf-8" enctype="multipart/form-data">
<?php if($show_succes_msg){ ?>
                <div class="row">
                    <div class="column six">
                        <h2>Thank you</h2>
                        <p>Thank you for your message. I'll respond as quickly as possible.</p>
                    </div>
                </div>
<?php } else {?>
                <div class="row">
                    <div class="column three">
                        <p<?php if($error[1]) echo ' class="error"><big>Can\'t be Empty</big' ?>>
                            <label for="frm_name">Name</label>
                            <input type="text" name="frm_name" value="<?php echo $_POST['frm_name'] ?>" id="frm_name" />
                        </p>
                    </div>
                    <div class="column three">
                        <p<?php if($error[2]) echo ' class="error"><big>Can\'t be Empty</big' ?>>
                            <label for="frm_email">Email</label>
                            <input type="text" name="frm_email" value="<?php echo $_POST['frm_email'] ?>" id="frm_email" />
                        </p>
                    </div>                    
                </div>
                <div class="row">
                    <div class="column three">
                        <p<?php if($error[3]) echo ' class="error"><big>Can\'t be Empty</big' ?>>
                            <label for="frm_subject">Subject</label>
                            <input type="text" name="frm_subject" value="<?php echo $_POST['frm_subject'] ?>" id="frm_subject" />
                        </p>
                    </div>
                </div>
                <div class="row">
                    <div class="column six">
                        <p<?php if($error[4]) echo ' class="error"><big>You don\'t wanna say anything?</big' ?>>
                            <label for="frm_comments">Message</label>
                            <textarea name="frm_comments" id="frm_message" rows="8" cols="40"><?php echo stripslashes($_POST['frm_comments']) ?></textarea>
                            
                            <button type="submit" name="submit">Send</button>
                        </p>
                    </div>
                </div>
            </form>
<?php } ?>            
        </div>
        <div id="footer">
            <p><strong>Copyright &copy; 2012</strong> by <a href="#">Harrison Sweeney</a>. Design by <a href="http://jonnotie.nl">Jonnotie</a></p>
        </div>
    </div>
</body>
</html>