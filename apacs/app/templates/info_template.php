<!DOCTYPE html>
<html>
    <head>
        <title>kbhkilder.dk API <?php echo $obj['api_version'] ?></title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <!-- Latest compiled and minified CSS, Twitter Bootstrap -->
        <link rel="stylesheet" href="http://netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap.min.css">
    </head>
    <body>
        <div class="container">
            <div class="row-fluid">
                <div class="span9">
                    <h2>KSA Backend, API v. 0.1</h2>
                    <p>The infrastructure behind web solutions from The City Archive of Copenhagen</p>
                    <p>The structure of the data is as follows: Each collection consists of a large amount of images. These images are filtered down based on one or more <i>levels</i>. As an example, Politiets Mandtaller consists of 3 such levels (<i>year</i>, <i>month</i> and <i>streetname</i>).</p>
                    <p>Depending on the collection, those levels can work as "tags" (when there is no interdependence between the different levels), or as a hierarchy, in which one level acts as a filter for possible values in other levels. </p>
                    <p>Each level has an amount of predefined filter values, used to get to the images. These values are received using the <i>metadata</i> service.</p>
                    <p>To get images and their metadata, get the collection and its levels, fill out mandatory levels with a value, and enjoy the returned data, using the <i>data</i> service.</p>
                </div>             
                <div class="span9">
                    <h4>Collections</h4>
                    <?php foreach($obj['collections'] as $collection){ ?>

                    <h5><?php echo $collection['long_name']; ?></h5>
                    <p><?php echo $collection['description']; ?></p>
                    <ul>
                        <li>Info: <a href="<?php echo $collection['infoUrl']; ?>"><?php echo $collection['infoUrl']; ?></a></li>
                        <li>Konfiguration (JSON): <a href="<?php echo $collection['jsonUrl']; ?>"><?php echo $collection['jsonUrl']; ?></a></li>
                    </ul>
                    <?php } ?>
                </div>             
            </div>
        </div>
    </body>
</html>
