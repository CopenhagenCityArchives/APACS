<!DOCTYPE html>
<html>
    <head>
        <title>API for <?php echo $obj['long_name']; ?></title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <!-- Latest compiled and minified CSS, Twitter Bootstrap -->
        <link rel="stylesheet" href="http://netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap.min.css">
    </head>
    <body>
        <div class="container">
            <div class="row-fluid">
                <span class="span9">
                    <h2>API for <?php echo $obj['long_name']; ?></h2>
                    <!--<p>
                        <span class="label label-success">Service</span> <strong>http://www.kbhkilder.dk/api/collections/<?php echo $obj['id']; ?></strong>
                    </p>-->
                    <p><?php echo $obj['info']; ?></p>
                    <!--<span class="label label-info">Example</span>
                    <a target="_blank" href="http://www.kbhkilder.dk/api/collections">http://www.kbhkilder.dk/api/collections/<?php echo $obj['id']; ?></a>-->
                </span>
                <span class='span9'>
                    <h2>Metadata</h2>
                </span>
                <?php if($obj['levels']){ foreach($obj['levels'] as $level){ ?>
                <?php if($level['searchable']){ ?>
                <div class="span9">
                    <h4><?php echo $level['gui_name']; ?></h4>
                    <p>
                        <span class="label label-success">Service</span> <strong><?php echo $level['url'] ?></strong>
                    </p>
                    <p><?php echo $level['gui_description']; ?></p>
                    <p>
                        Påkrævede parametre:
                    </p>
                    <?php if(($level['required_levels'])) { ?>
                    <ul>
                        <?php foreach($level['required_levels'] as $filter){ ?>
                            <li><strong>:<?php echo $filter; ?></strong> <?php echo $level['api_description']; ?></li>
                        <?php } ?>
                    </ul>
                    <?php } else{ ?>
                    <ul>
                        <li>Ingen</li>
                    </ul>
                    <?php } ?>
                    <span class="label label-info">Example</span>
                    <a target="_blank" href="<?php echo $level['url'] ?><?php echo $level['required_levels_url']; ?>"><?php echo $level['url'] ?><?php echo $level['required_levels_url']; ?></a>
                    <div class="span9">&nbsp;</div>
                </div>
                <?php } ?>
                <?php } ?>
                <div class="span9">&nbsp;</div>
                <?php } ?>
                <div class="span9">
                    <h2>Billeder</h2>
                    <p>
                        <span class="label label-success">Service</span> <strong><?php echo 'http://' . $_SERVER['HTTP_HOST']; ?>/api/data/<?php echo $obj['id']; ?></strong>
                    </p>
                    <p>Henter billeder for en given collection baseret på filtre.</p>
                    <p>
                        Parametre:
                    </p>
                    <?php if(($obj['data_filters'])) { ?>
                    <ul>
                        <?php foreach($obj['data_filters'] as $filter){ ?>
                            <?php if($filter['searchable']){ ?>
                            <li><strong>:<?php echo $filter['name']; ?></strong> <?php echo $filter['api_description']; ?> <?php if($filter['required']){?>  <span class="label label-warning">Påkrævet</span> <?php } ?></li>
                            <?php } ?>
                        <?php } ?>
                    </ul>
                    <?php } else{ ?>
                    <ul>
                        <li>Ingen</li>
                    </ul>
                    <?php } ?>
                    <span class="label label-info">Example</span>
                    <a target="_blank" href="<?php echo $obj['data_url']; ?>"><?php echo $obj['data_url']; ?></a>
                </div>

            </div>
        </div>
    </body>
</html>
