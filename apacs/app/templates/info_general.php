<!DOCTYPE html>
<html>
    <head>
        <title>Kbhkilder.dk</title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <!-- Latest compiled and minified CSS, Twitter Bootstrap -->
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap.min.css">
    </head>
    <body>
        <div class="container">
            <div class="row-fluid">
                <div>
                    <div class="span9">
                        <h2>Kbhkilder.dk</h2>
                        <p>Kbhkilder.dk udgør den centrale indgang til Københavns Stadsarkivs digitaliserede kilder og deres metadata.</p>
                        <p>Disse data er tilgængelige gennem et JSON-baseret REST-API.</p>
                        <p>Herunder ses grundlæggende informationer om hver digitaliseret samling.</p>
                        <p>&nbsp;</p>
                    </div>
                    <!--<p>
                        <span class="label label-success">Service</span> <strong>https://www.kbhkilder.dk/api/collections/<?php echo $obj['id']; ?></strong>
                    </p>-->
                </div>
                <div class="span9">
                    <h3>Overblik overblik over kilder i Kildeviseren</h3>
                    <p>Bemærk at der kun er tale om collections med id >= 100.</p>
                    <p>Særlige collections:</p>
                    <p>Collection 1 (Begravelsesprotokollerne) er Task-udgaven af en prædefineret collection. Den skal overføres fra Starbas ved lejlighed.</p>
                    <p>Collection 60 (Politiets Registerblade) er en midlertidig overførsel af metadata, som højst sandsynligt skal fjernes igen.</p>
                    <p>&nbsp;</p>
                    <p><b>Bemærk at der til det totale sideantal skal lægges 1,4 millioner registerblade, 3,7 millioner mandtaller og 150.000 siders begravelsesprotokoller.</b></p>
                    <div class='span9'>
                        <h4>Protokoller og sider i alt (publiceret/total)</h4>
                    </div>
                    <div class="span9">
                        <p>Protokoller: <b><?php echo number_format($totals['public_units'], 0, ',', '.'); ?> / <?php echo number_format($totals['units'], 0, ',', '.'); ?></b></p>
                        <p>Sider: <b><?php echo number_format($totals['public_pages'], 0, ',', '.'); ?> / <?php echo number_format($totals['pages'], 0, ',', '.'); ?></b></p>
                        <?php if($totals['units_without_pages'] > 0){ ?>
                            <p>Der er <?php echo $totals['units_without_pages']; ?> protokoller uden tilknyttede sider</p>
                        <?php } ?>
                    </div>
                    <div class="span9">&nbsp;</div>
                    <div class='span9'>
                        <h4>Sidevisninger det seneste døgn: <?php echo number_format($totals['displayCount'], 0, ',', '.'); ?></h4>
                    </div>
                    <div class="span9">&nbsp;</div>

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
                    <p>&nbsp;</p>
                    <!--<span class="label label-info">Eksempel</span>
                    <a target="_blank" href="https://www.kbhkilder.dk/api/collections">https://www.kbhkilder.dk/api/collections/<?php echo $obj['id']; ?></a>-->
                </div>
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
