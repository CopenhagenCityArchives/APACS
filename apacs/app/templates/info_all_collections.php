<!DOCTYPE html>
<html>
    <head>
        <title>Kildeoverblik</title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <!-- Latest compiled and minified CSS, Twitter Bootstrap -->
        <link rel="stylesheet" href="http://netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap.min.css">
    </head>
    <body>
        <div class="container">
            <div class="row-fluid">
                <span class="span9">
                    <h2>Kildeoverblik</h2>
                    <!--<p>
                        <span class="label label-success">Service</span> <strong>http://www.kbhkilder.dk/api/collections/<?php echo $obj['id']; ?></strong>
                    </p>-->
                    <p>Kort overblik over kilder i Kildeviseren</p>
                    <p>Bemærk at der kun er tale om collections >= 100</p>
                    <p>Collection 1 (Begravelsesprotokollerne) er Task-udgaven af en prædefineret collection. Den skal overføres fra Starbas ved lejlighed.</p>
                    <p>Collection 60 (Politiets Registerblade) er en midlertidig overførsel af metadata, som højst sandsynligt skal fjernes igen.</p>
                    <h3>Indhold</h3>
                    <div class="span9">
                        <ul>
                        <?php foreach($cols as $obj){ ?>
                            <?php if(!is_null($obj['stats'])){ ?>
                            <li><a href="#collection-<?php echo $obj['id']; ?>"><?php echo $obj['name']; ?></a></li>
                            <?php } ?>
                        <?php } ?>
                        </ul>
                    </div>
                    <span class='span9'>
                        <h3>Protokoller og sider i alt (publiceret/total)</h3>
                    </span>
                    <div class="span9">
                        <p>Protokoller: <b><?php echo number_format($totals['public_units'], 0, ',', '.'); ?> / <?php echo number_format($totals['units'], 0, ',', '.'); ?></b></p>
                        <p>Sider: <b><?php echo number_format($totals['public_pages'], 0, ',', '.'); ?> / <?php echo number_format($totals['pages'], 0, ',', '.'); ?></b></p>
                        <?php if($totals['units_without_pages'] > 0){ ?>
                            <p>Der er <?php echo $totals['units_without_pages']; ?> protokoller uden tilknyttede sider</p>
                        <?php } ?>
                    </div>
                    <div class="span9">&nbsp;</div>
                    <div class="span9">&nbsp;</div>
                    <!--<span class="label label-info">Eksempel</span>
                    <a target="_blank" href="http://www.kbhkilder.dk/api/collections">http://www.kbhkilder.dk/api/collections/<?php echo $obj['id']; ?></a>-->
                </span>
                <?php foreach($cols as $obj){ ?>
                    <?php if(!is_null($obj['stats'])){ ?>
                    <h3 id="collection-<?php echo $obj['id']; ?>"><?php echo $obj['name']; ?></h3>
                    <p>Collection id <?php echo $obj['id']; ?></p>
                    <span class='span9'>
                        <h4>Protokoller og sider (publiceret/total)</h4>
                    </span>
                    <div class="span9">
                        <p>Protokoller: <b><?php echo number_format($obj['stats']['public_units'], 0, ',', '.'); ?> / <?php echo number_format($obj['stats']['units'], 0, ',', '.'); ?></b></p>
                        <p>Sider: <b><?php echo number_format($obj['stats']['public_pages'], 0, ',', '.'); ?> / <?php echo number_format($obj['stats']['pages'], 0, ',', '.'); ?></b></p>
                        <?php if($obj['stats']['units_without_pages'] > 0){ ?>
                            <p>Der er <?php echo $obj['stats']['units_without_pages']; ?> protokoller uden tilknyttede sider</p>
                        <?php } ?>
                    </div>
                    <p>
                        <span class="label label-success">Info</span> <a href="<?php echo $obj['api_documentation_url']; ?>" target="_blank"><?php echo $obj['api_documentation_url']; ?></a>
                    </p>
                    <div class="span9">&nbsp;</div>
                    <?php } ?>

                <?php } ?>
            </div>
        </div>
    </body>
</html>
