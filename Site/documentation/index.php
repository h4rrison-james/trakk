<?php
    $page ="home"; 
    $title ="Choose";
    
    include "includes/header.php"; 
?>
    <div class="row">
        <div class="column w12">
            <p>Thank you for purchasing my theme. If you have any questions that are beyond the scope of this help file, please feel free to email on <a href="templates@jonnotie.nl">templates@jonnotie.nl</a>. Thanks so much!</p>
        </div>
    </div>
    <div class="row">
        <div class="column w12">
            <h1>Getting started</h1>
        </div>
    </div>
    <div class="row">
        <div class="column w4">
            <h2>Change icon</h2>
        </div>
        <div class="column w8">
            <p>Go to '<strong>/templatefolder/psd/assets/icon.psd</strong>' (you need photoshop for this.). Change the icon and save it as a <strong>transparant png</strong> to '<strong>/images/essentials/header/title/icon.png</strong>'. Make sure it's not bigger than 61x65px.</p>
        </div>
    </div>
    <hr />
    <div class="row">
        <div class="column w4">
            <h2>Understand the content framework</h2>
        </div>
        <div class="column w8">
            <p>I've set this html up so it's easy to understand and copy. Go take a look in <strong><a href="http://templates.jonnotie.nl/iphone/about.html">about.html</a></strong>'s source. The framework is located in the div with id '<strong>content</strong>'.</p>
        </div>
    </div>
    <hr />
    <div class="row">
        <div class="column w4">
            <h2>Email address for the contact form</h2>
        </div>
        <div class="column w8">
            <p>Go to line <strong>25</strong> and change the email addresses to yours.</p>
            <code>mail(&quot;<strong>your@domain.com</strong>&quot;, &quot;yourapp: &#x27;&quot; . $subject . &quot;&#x27;&quot;,
    			&quot;Name: \t\t\t&quot; . $name . &quot;\n\nEmail:\t\t\t&quot;. $mail .&quot;\n\Message: \n&quot; . stripslashes($comments) . &quot;\n\n&quot;,
    			&quot;From: <strong>noreply@domain.com</strong>\r\nReply-To: $mail\r\n&quot;);</code>
        </div>
    </div>
    
    <hr />
    <div class="row">
        <div class="column w12">
            <h1>The Basics</h1>
        </div>
    </div>
    <div class="row">
        <div class="column w4">
            <h2>The files</h2>
        </div>
        <div class="column w8">
            <p>The files are ordered like almost all websites. The images are located in '<strong>images</strong>'. The Javascript files are located in '<strong>javascripts</strong>'. In '<strong>media</strong>' you find the movie that plays in the iPhone. The css can be found in '<strong>stylesheets</strong>'. The contact form function is located in '<strong>includes</strong>'.</p>
        </div>
    </div>
    <hr />
    <div class="row">
        <div class="column w4">
            <h2>CSS structure</h2>
        </div>
        <div class="column w8">
            <p>The basic css selectors like a, p, ul, body etc can be found below this css comment:</p>
            <code>/* @group basics */</code>
            
            <p>The essential selectors like #header, #content, #main, #footer can be found below this css comment:</p>
            <code>/* @group essentials */</code>
            <p><strong>I highly recommend using Firefox's well known <a href="https://addons.mozilla.org/nl/firefox/addon/1843">Firebug</a> plugin for looking in the code.</strong></p>
            
        </div>
    </div>
    <div class="row">
        <div class="column w12">
            <h1>Template tweaking</h1>
        </div>
    </div>
    <div class="row">
        <div class="column w4">
            <h2>Mirror the whole page</h2>
        </div>
        <div class="column w8">
            <p>Search for the <strong>&lt;body&gt;</strong> tag and add a '<strong>mirror</strong>' class to it. On every page.</p>
            <code>&lt;body <strong>class=&quot;mirror&quot;</strong>&gt;</code>
        </div>
    </div>
    <hr />
    <div class="row">
        <div class="column w4">
            <h2>Thumbnail images</h2>
        </div>
        <div class="column w8">
            <p>The thumbnail images can be found in '<strong>/media/</strong>'.</p>
            <p>The images inside the '<strong>.link</strong>' class are 86px wide. The images inside '<strong>.link wide</strong>' class are 206px wide.</p>
        </div>
    </div>
    <hr />
    <div class="row">
        <div class="column w4">
            <h2>Replace movie in iPhone with image</h2>
        </div>
        <div class="column w8">
            <p>Replace</p>
            <code>&lt;div class=&quot;hvlog {width: &#x27;230&#x27;, height: &#x27;346&#x27;, controller: &#x27;false&#x27;, loop: &#x27;true&#x27;, pluginspage: &#x27;http://www.apple.com/quicktime/download/&#x27;}&quot;&gt;
                &lt;a href=&quot;media/example.mov&quot; rel=&quot;enclosure&quot;&gt;click to play&lt;/a&gt;
                &lt;img src=&quot;media/screenshot-2.png&quot; alt=&quot;&quot; /&gt;
            &lt;/div&gt;</code>
            <p>With</p>
            <code>&lt;img src=&quot;media/screenshot-1.png&quot; alt=&quot;&quot; /&gt;</code>
        </div>
    </div>
    <hr />
    <div class="row">
        <div class="column w4">
            <h2>Autoplay movie in iPhone</h2>
        </div>
        <div class="column w8">
            <p>Replace</p>
            <code>&lt;div class=&quot;hvlog {width: &#x27;230&#x27;, height: &#x27;346&#x27;, controller: &#x27;false&#x27;, loop: &#x27;true&#x27;, pluginspage: &#x27;http://www.apple.com/quicktime/download/&#x27;}&quot;&gt;
                &lt;a href=&quot;media/example.mov&quot; rel=&quot;enclosure&quot;&gt;click to play&lt;/a&gt;
                &lt;img src=&quot;media/screenshot-2.png&quot; alt=&quot;&quot; /&gt;
            &lt;/div&gt;</code>
            <p>With</p>
            <code>&lt;embed src=&quot;media/example.mov&quot; width=&quot;230&quot; height=&quot;346&quot; autoplay=&quot;true&quot; controller=&quot;false&quot; wmode=&quot;transparent&quot; /&gt;</code>
        </div>
    </div>
    <hr />
    <div class="row">
        <div class="column w4">
            <h2>Change 'available in the app store' to 'soon available in the app store'</h2>
        </div>
        <div class="column w8">
            <p>Search for the class named '<strong>appstore</strong>' and replace it with '<strong>appstore-soon</strong>'.</p>
            <code>&lt;a href=&quot;http://itunes.apple.com/WebObjects/MZStore.woa/wa/viewSoftware?id=296415944&amp;amp;mt=8&quot;<strong> class=&quot;appstore-soon&quot;</strong>&gt;Available in the apple store&lt;/a&gt;</code>
        </div>
    </div>
    <?php include "includes/footer.php"; ?>
</body>
</html>