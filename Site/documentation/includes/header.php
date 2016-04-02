<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta http-equiv="imagetoolbar" content="no" />

    <title>Jonnotie › <? echo ucfirst($title);?></title>
    
    <script src="/mint/?js" type="text/javascript"></script>
       
    <link rel="stylesheet" type="text/css" media="screen" href="css/main.css" />
    
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js" type="text/javascript"></script>
</head>
<body id="p-<? echo $page ;?>">
    <div id="navigation">
        <ul class="row">
            <li class="nav-home"><a href="/">Home</a></li>
            <li class="nav-work"><a href="/work">Work</a></li>
            <li class="nav-contact"><a href="/contact">Contact</a></li>
        </ul>
    </div>
    <div id="header" class="row">
        <div class="column w4">
            <? if($page == "home"){ ?> <a id="logo">Jonnotie</a>
            <? } else { ?> <a id="logo" href="/">Jonnotie</a><? }; ?>
        </div>
        <!-- <h1><? echo $title ;?></h1> -->
        <div class="column w8">
            <div class="row">
                <div class="column w4">
                    <h2>hello</h2>
                    <p>This is the documentation for a theme called '<a href="http://templates.jonnotie.nl/iphone/">iphone</a>'.</p> 
                </div>
                <div class="column w4">
                    <h2>contact</h2>
                    <p>Need help? Need to figure something out?. Drop me a line at <a href="mailto:templates@jonnotie.nl">templates@jonnotie.nl</a>.</p>
                </div>
            </div>
        </div>
    </div>
